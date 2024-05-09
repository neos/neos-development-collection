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
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection;
use Neos\ContentGraph\DoctrineDbalAdapter\NodeQueryBuilder;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
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
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

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
 *    - dsp -> dimension space point, resolves hashes to full dimension coordinates
 *    - cdsp -> child dimension space point, same as dsp for child queries
 *    - pdsp -> parent dimension space point, same as dsp for parent queries
 *
 * @internal the parent interface {@see ContentGraphInterface} is API
 */
final class ContentGraph implements ContentGraphInterface
{
    private readonly NodeQueryBuilder $nodeQueryBuilder;

    /**
     * @var array<string,ContentSubgraphWithRuntimeCaches>
     */
    private array $subgraphs = [];

    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly NodeFactory $nodeFactory,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly ContentGraphTableNames $tableNames,
        public readonly WorkspaceName $workspaceName,
        public readonly ContentStreamId $contentStreamId
    ) {
        $this->nodeQueryBuilder = new NodeQueryBuilder($this->client->getConnection(), $this->tableNames);
    }

    public function getSubgraph(
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        $index = $dimensionSpacePoint->hash . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subgraphs[$index])) {
            $this->subgraphs[$index] = new ContentSubgraphWithRuntimeCaches(
                new ContentSubgraph(
                    $this->contentRepositoryId,
                    $this->workspaceName,
                    $this->contentStreamId,
                    $dimensionSpacePoint,
                    $visibilityConstraints,
                    $this->client,
                    $this->nodeFactory,
                    $this->nodeTypeManager,
                    $this->tableNames
                )
            );
        }

        return $this->subgraphs[$index];
    }

    /**
     * @throws RootNodeAggregateDoesNotExist
     */
    public function findRootNodeAggregateByType(
        NodeTypeName $nodeTypeName
    ): NodeAggregate {
        $rootNodeAggregates = $this->findRootNodeAggregates(
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
        FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates {
        $rootNodeAggregateQueryBuilder = $this->nodeQueryBuilder->buildFindRootNodeAggregatesQuery($this->contentStreamId, $filter);
        return NodeAggregates::fromArray(iterator_to_array($this->mapQueryBuilderToNodeAggregates($rootNodeAggregateQueryBuilder)));
    }

    public function findNodeAggregatesByType(
        NodeTypeName $nodeTypeName
    ): iterable {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery();
        $queryBuilder
            ->andWhere('n.nodetypename = :nodeTypeName')
            ->setParameters([
                'contentStreamId' => $this->contentStreamId->value,
                'nodeTypeName' => $nodeTypeName->value,
            ]);
        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findNodeAggregateById(
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery()
            ->andWhere('n.nodeaggregateid = :nodeAggregateId')
            ->orderBy('n.relationanchorpoint', 'DESC')
            ->setParameters([
                'nodeAggregateId' => $nodeAggregateId->value,
                'contentStreamId' => $this->contentStreamId->value
            ]);

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $this->fetchRows($queryBuilder),
            $this->contentStreamId,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findParentNodeAggregates(
        NodeAggregateId $childNodeAggregateId
    ): iterable {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery()
            ->innerJoin('n', $this->nodeQueryBuilder->contentGraphTableNames->hierachyRelation(), 'ch', 'ch.parentnodeanchor = n.relationanchorpoint')
            ->innerJoin('ch', $this->nodeQueryBuilder->contentGraphTableNames->node(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->andWhere('ch.contentstreamid = :contentStreamId')
            ->andWhere('cn.nodeaggregateid = :nodeAggregateId')
            ->setParameters([
                'nodeAggregateId' => $childNodeAggregateId->value,
                'contentStreamId' => $this->contentStreamId->value
            ]);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->contentStreamId);
        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(NodeAggregateId $childNodeAggregateId, OriginDimensionSpacePoint $childOriginDimensionSpacePoint): ?NodeAggregate
    {
        $subQueryBuilder = $this->createQueryBuilder()
            ->select('pn.nodeaggregateid')
            ->from($this->nodeQueryBuilder->contentGraphTableNames->node(), 'pn')
            ->innerJoin('pn', $this->nodeQueryBuilder->contentGraphTableNames->hierachyRelation(), 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->nodeQueryBuilder->contentGraphTableNames->node(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->where('ch.contentstreamid = :contentStreamId')
            ->andWhere('ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash')
            ->andWhere('cn.nodeaggregateid = :childNodeAggregateId')
            ->andWhere('cn.origindimensionspacepointhash = :childOriginDimensionSpacePointHash');

        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid, h.subtreetags, dsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->nodeQueryBuilder->contentGraphTableNames->node(), 'n')
            ->innerJoin('n', $this->nodeQueryBuilder->contentGraphTableNames->hierachyRelation(), 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->innerJoin('h', $this->nodeQueryBuilder->contentGraphTableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->where('n.nodeaggregateid = (' . $subQueryBuilder->getSQL() . ')')
            ->andWhere('h.contentstreamid = :contentStreamId')
            ->setParameters([
                'contentStreamId' => $this->contentStreamId->value,
                'childNodeAggregateId' => $childNodeAggregateId->value,
                'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->hash,
            ]);

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $this->fetchRows($queryBuilder),
            $this->contentStreamId,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findTetheredChildNodeAggregates(NodeAggregateId $parentNodeAggregateId): iterable
    {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->contentStreamId)
            ->andWhere('cn.classification = :tetheredClassification')
            ->setParameter('tetheredClassification', NodeAggregateClassification::CLASSIFICATION_TETHERED->value);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(NodeName $nodeName, NodeAggregateId $parentNodeAggregateId, OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint, DimensionSpacePointSet $dimensionSpacePointsToCheck): DimensionSpacePointSet
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('dsp.dimensionspacepoint, h.dimensionspacepointhash')
            ->from($this->nodeQueryBuilder->contentGraphTableNames->hierachyRelation(), 'h')
            ->innerJoin('h', $this->nodeQueryBuilder->contentGraphTableNames->node(), 'n', 'n.relationanchorpoint = h.parentnodeanchor')
            ->innerJoin('h', $this->nodeQueryBuilder->contentGraphTableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->innerJoin('n', $this->nodeQueryBuilder->contentGraphTableNames->hierachyRelation(), 'ph', 'ph.childnodeanchor = n.relationanchorpoint')
            ->where('n.nodeaggregateid = :parentNodeAggregateId')
            ->andWhere('n.origindimensionspacepointhash = :parentNodeOriginDimensionSpacePointHash')
            ->andWhere('ph.contentstreamid = :contentStreamId')
            ->andWhere('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash IN (:dimensionSpacePointHashes)')
            ->andWhere('h.name = :nodeName')
            ->setParameters([
                'parentNodeAggregateId' => $parentNodeAggregateId->value,
                'parentNodeOriginDimensionSpacePointHash' => $parentNodeOriginDimensionSpacePoint->hash,
                'contentStreamId' => $this->contentStreamId->value,
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

    /**
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregatesByName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): iterable {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->contentStreamId)
            ->andWhere('ch.name = :relationName')
            ->setParameter('relationName', $name->value);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function countNodes(): int
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($this->nodeQueryBuilder->contentGraphTableNames->node());
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
        return array_map(
            static fn (array $row) => NodeTypeName::fromString($row['nodetypename']),
            $this->fetchRows($this->nodeQueryBuilder->buildfindUsedNodeTypeNamesQuery())
        );
    }

    /**
     * @return ContentSubgraphWithRuntimeCaches[]
     * @internal only used for {@see DoctrineDbalContentGraphProjection}
     */
    public function getSubgraphs(): array
    {
        return $this->subgraphs;
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
            $this->contentStreamId,
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

    /** The workspace this content graph is operating on */
    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /** @internal The content stream id where the workspace name points to for this instance */
    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }
}
