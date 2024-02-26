<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphWithRuntimeCaches\ContentSubgraphWithRuntimeCaches;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The Doctrine DBAL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * ## Conventions for SQL queries
 *
 *  - n -> node
 *  - h -> hierarchy edge
 *
 *  - if more than one node (parent-child)
 *    - pn -> parent node
 *    - cn -> child node
 *    - h -> the hierarchy edge connecting parent and child
 *    - ph -> the hierarchy edge incoming to the parent (sometimes relevant)
 *
 * @internal the parent interface {@see ContentGraphInterface} is API
 */
final class ContentGraph implements ContentGraphInterface
{
    /**
     * @var array<string,ContentSubgraphWithRuntimeCaches>
     */
    private array $subgraphs = [];

    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly NodeFactory $nodeFactory,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix
    ) {
    }

    final public function getSubgraph(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        $index = $contentStreamId->value . '-' . $dimensionSpacePoint->hash . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subgraphs[$index])) {
            $this->subgraphs[$index] = new ContentSubgraphWithRuntimeCaches(
                new ContentSubgraph(
                    $this->contentRepositoryId,
                    $contentStreamId,
                    $dimensionSpacePoint,
                    $visibilityConstraints,
                    $this->client,
                    $this->nodeFactory,
                    $this->nodeTypeManager,
                    $this->tableNamePrefix
                )
            );
        }

        return $this->subgraphs[$index];
    }

    /**
     * @throws RootNodeAggregateDoesNotExist
     */
    public function findRootNodeAggregateByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): NodeAggregate {
        $rootNodeAggregates = $this->findRootNodeAggregates(
            $contentStreamId,
            FindRootNodeAggregatesFilter::create(nodeTypeName: $nodeTypeName)
        );

        if ($rootNodeAggregates->count() > 1) {
            $ids = [];
            foreach ($rootNodeAggregates as $rootNodeAggregate) {
                $ids[] = $rootNodeAggregate->nodeAggregateId->value;
            }
            throw new \RuntimeException(sprintf(
                'More than one root node aggregate of type "%s" found (IDs: %s).',
                $nodeTypeName->value,
                implode(', ', $ids)
            ));
        }

        $rootNodeAggregate = $rootNodeAggregates->first();

        if ($rootNodeAggregate === null) {
            throw RootNodeAggregateDoesNotExist::butWasExpectedTo($nodeTypeName);
        }

        return $rootNodeAggregate;
    }

    public function findRootNodeAggregates(
        ContentStreamId $contentStreamId,
        FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates {
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.contentstreamid, h.name, h.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.parentnodeanchor = :rootEdgeParentAnchorId')
            ->setParameters([
                'contentStreamId' => $contentStreamId->value,
                'rootEdgeParentAnchorId' => NodeRelationAnchorPoint::forRootEdge()->value,
            ]);

        if ($filter->nodeTypeName !== null) {
            $queryBuilder
                ->andWhere('n.nodetypename = :nodeTypeName')
                ->setParameter('nodeTypeName', $filter->nodeTypeName->value);
        }
        return NodeAggregates::fromArray(iterator_to_array($this->mapQueryBuilderToNodeAggregates($queryBuilder)));
    }

    public function findNodeAggregatesByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): iterable {
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.contentstreamid, h.name, h.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('n.nodetypename = :nodeTypeName')
            ->setParameters([
                'contentStreamId' => $contentStreamId->value,
                'nodeTypeName' => $nodeTypeName->value,
            ]);
        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findNodeAggregateById(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid, h.dimensionspacepoint AS covereddimensionspacepoint, r.dimensionspacepointhash AS disableddimensionspacepointhash')
            ->from($this->tableNamePrefix . '_hierarchyrelation', 'h')
            ->innerJoin('h', $this->tableNamePrefix . '_node', 'n', 'n.relationanchorpoint = h.childnodeanchor')
            ->leftJoin('h', $this->tableNamePrefix . '_restrictionrelation', 'r', 'r.originnodeaggregateid = n.nodeaggregateid AND r.contentstreamid = :contentStreamId AND r.affectednodeaggregateid = n.nodeaggregateid AND r.dimensionspacepointhash = h.dimensionspacepointhash')
            ->where('n.nodeaggregateid = :nodeAggregateId')
            ->andWhere('h.contentstreamid = :contentStreamId')
            ->setParameters([
                'nodeAggregateId' => $nodeAggregateId->value,
                'contentStreamId' => $contentStreamId->value
            ]);

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $this->fetchRows($queryBuilder),
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findParentNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId
    ): iterable {
        $queryBuilder = $this->createQueryBuilder()
            ->select('pn.*, ph.name, ph.contentstreamid, ph.dimensionspacepoint AS covereddimensionspacepoint, r.dimensionspacepointhash AS disableddimensionspacepointhash')
            ->from($this->tableNamePrefix . '_node', 'pn')
            ->innerJoin('pn', $this->tableNamePrefix . '_hierarchyrelation', 'ph', 'ph.childnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->tableNamePrefix . '_hierarchyrelation', 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->tableNamePrefix . '_node', 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->leftJoin('ph', $this->tableNamePrefix . '_restrictionrelation', 'r', 'r.originnodeaggregateid = pn.nodeaggregateid AND r.contentstreamid = :contentStreamId AND r.affectednodeaggregateid = pn.nodeaggregateid AND r.dimensionspacepointhash = ph.dimensionspacepointhash')
            ->where('cn.nodeaggregateid = :nodeAggregateId')
            ->andWhere('ph.contentstreamid = :contentStreamId')
            ->andWhere('ch.contentstreamid = :contentStreamId')
            ->setParameters([
                'nodeAggregateId' => $childNodeAggregateId->value,
                'contentStreamId' => $contentStreamId->value
            ]);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $subQueryBuilder = $this->createQueryBuilder()
            ->select('pn.nodeaggregateid')
            ->from($this->tableNamePrefix . '_node', 'pn')
            ->innerJoin('pn', $this->tableNamePrefix . '_hierarchyrelation', 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->tableNamePrefix . '_node', 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->where('ch.contentstreamid = :contentStreamId')
            ->andWhere('ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash')
            ->andWhere('cn.nodeaggregateid = :childNodeAggregateId')
            ->andWhere('cn.origindimensionspacepointhash = :childOriginDimensionSpacePointHash');

        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid, h.dimensionspacepoint AS covereddimensionspacepoint, r.dimensionspacepointhash AS disableddimensionspacepointhash')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->leftJoin('h', $this->tableNamePrefix . '_restrictionrelation', 'r', 'r.originnodeaggregateid = n.nodeaggregateid AND r.contentstreamid = :contentStreamId AND r.affectednodeaggregateid = n.nodeaggregateid AND r.dimensionspacepointhash = h.dimensionspacepointhash')
            ->where('n.nodeaggregateid = (' . $subQueryBuilder->getSQL() . ')')
            ->andWhere('h.contentstreamid = :contentStreamId')
            ->setParameters([
                'contentStreamId' => $contentStreamId->value,
                'childNodeAggregateId' => $childNodeAggregateId->value,
                'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->hash,
            ]);

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $this->fetchRows($queryBuilder),
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $queryBuilder = $this->buildChildNodeAggregateQuery($parentNodeAggregateId, $contentStreamId);
        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregatesByName(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): iterable {
        $queryBuilder = $this->buildChildNodeAggregateQuery($parentNodeAggregateId, $contentStreamId)
            ->andWhere('ch.name = :relationName')
            ->setParameter('relationName', $name->value);
        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findTetheredChildNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $queryBuilder = $this->buildChildNodeAggregateQuery($parentNodeAggregateId, $contentStreamId)
            ->andWhere('cn.classification = :tetheredClassification')
            ->setParameter('tetheredClassification', NodeAggregateClassification::CLASSIFICATION_TETHERED->value);
        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeName $nodeName
     * @param NodeAggregateId $parentNodeAggregateId
     * @param OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint
     * @param DimensionSpacePointSet $dimensionSpacePointsToCheck
     * @return DimensionSpacePointSet
     */
    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamId $contentStreamId,
        NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        $queryBuilder = $this->createQueryBuilder()
            ->select('h.dimensionspacepoint, h.dimensionspacepointhash')
            ->from($this->tableNamePrefix . '_hierarchyrelation', 'h')
            ->innerJoin('h', $this->tableNamePrefix . '_node', 'n', 'n.relationanchorpoint = h.parentnodeanchor')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'ph', 'ph.childnodeanchor = n.relationanchorpoint')
            ->where('n.nodeaggregateid = :parentNodeAggregateId')
            ->andWhere('n.origindimensionspacepointhash = :parentNodeOriginDimensionSpacePointHash')
            ->andWhere('ph.contentstreamid = :contentStreamId')
            ->andWhere('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash IN (:dimensionSpacePointHashes)')
            ->andWhere('h.name = :nodeName')
            ->setParameters([
                'parentNodeAggregateId' => $parentNodeAggregateId->value,
                'parentNodeOriginDimensionSpacePointHash' => $parentNodeOriginDimensionSpacePoint->hash,
                'contentStreamId' => $contentStreamId->value,
                'dimensionSpacePointHashes' => $dimensionSpacePointsToCheck->getPointHashes(),
                'nodeName' => $nodeName->value
            ], [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY,
            ]);
        $dimensionSpacePoints = [];
        foreach ($this->fetchRows($queryBuilder) as $hierarchyRelationData) {
            $dimensionSpacePoints[$hierarchyRelationData['dimensionspacepointhash']] = DimensionSpacePoint::fromJsonString($hierarchyRelationData['dimensionspacepoint']);
        }
        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    public function countNodes(): int
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($this->tableNamePrefix . '_node');
        $result = $queryBuilder->execute();
        if (!$result instanceof Result) {
            throw new \RuntimeException(sprintf('Failed to count nodes. Expected result to be of type %s, got: %s', Result::class, get_debug_type($result)), 1701444550);
        }
        try {
            return (int)$result->fetchOne();
        } catch (DriverException | DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch rows from database: %s', $e->getMessage()), 1701444590, $e);
        }
    }

    public function findUsedNodeTypeNames(): iterable
    {
        $rows = $this->fetchRows($this->createQueryBuilder()
            ->select('DISTINCT nodetypename')
            ->from($this->tableNamePrefix . '_node'));
        return array_map(static fn (array $row) => NodeTypeName::fromString($row['nodetypename']), $rows);
    }

    /**
     * @return ContentSubgraphWithRuntimeCaches[]
     * @internal only used for {@see DoctrineDbalContentGraphProjection}
     */
    public function getSubgraphs(): array
    {
        return $this->subgraphs;
    }

    private function buildChildNodeAggregateQuery(NodeAggregateId $parentNodeAggregateId, ContentStreamId $contentStreamId): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select('cn.*, ch.name, ch.contentstreamid, ch.dimensionspacepoint AS covereddimensionspacepoint, r.dimensionspacepointhash AS disableddimensionspacepointhash')
            ->from($this->tableNamePrefix . '_node', 'pn')
            ->innerJoin('pn', $this->tableNamePrefix . '_hierarchyrelation', 'ph', 'ph.childnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->tableNamePrefix . '_hierarchyrelation', 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->tableNamePrefix . '_node', 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->leftJoin('pn', $this->tableNamePrefix . '_restrictionrelation', 'r', 'r.originnodeaggregateid = pn.nodeaggregateid AND r.contentstreamid = :contentStreamId AND r.affectednodeaggregateid = pn.nodeaggregateid AND r.dimensionspacepointhash = ph.dimensionspacepointhash')
            ->where('pn.nodeaggregateid = :parentNodeAggregateId')
            ->andWhere('ph.contentstreamid = :contentStreamId')
            ->andWhere('ch.contentstreamid = :contentStreamId')
            ->orderBy('ch.position')
            ->setParameters([
                'parentNodeAggregateId' => $parentNodeAggregateId->value,
                'contentStreamId' => $contentStreamId->value,
            ]);
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->client->getConnection()->createQueryBuilder();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return iterable<NodeAggregate>
     */
    private function mapQueryBuilderToNodeAggregates(QueryBuilder $queryBuilder): iterable
    {
        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $this->fetchRows($queryBuilder),
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchRows(QueryBuilder $queryBuilder): array
    {
        $result = $queryBuilder->execute();
        if (!$result instanceof Result) {
            throw new \RuntimeException(sprintf('Failed to execute query. Expected result to be of type %s, got: %s', Result::class, get_debug_type($result)), 1701443535);
        }
        try {
            return $result->fetchAllAssociative();
        } catch (DriverException | DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch rows from database: %s', $e->getMessage()), 1701444358, $e);
        }
    }
}
