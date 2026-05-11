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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\HierarchyRelationStatement;
use Neos\ContentGraph\DoctrineDbalAdapter\HierarchyRelationViewStatement;
use Neos\ContentGraph\DoctrineDbalAdapter\NodeQueryBuilder;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
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
 *    - ch -> the hierarchy edge of the child (sometimes relevant)
 *    - dsp -> dimension space point, resolves hashes to full dimension coordinates
 *    - cdsp -> child dimension space point, same as dsp for child queries
 *    - pdsp -> parent dimension space point, same as dsp for parent queries
 *
 * @internal the parent interface {@see ContentGraphInterface} is API
 */
final class ContentGraph implements ContentGraphInterface
{
    private readonly NodeQueryBuilder $nodeQueryBuilder;

    private readonly HierarchyRelationStatement|HierarchyRelationViewStatement $hierarchyRelationStatement;

    public function __construct(
        private readonly Connection $dbal,
        private readonly NodeFactory $nodeFactory,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly ContentGraphTableNames $tableNames,
        public readonly WorkspaceName $workspaceName,
        public readonly ContentStreamId $contentStreamId,
        public readonly ContentStreamLayers $contentStreamLayers,
    ) {
        $this->hierarchyRelationStatement = match ($this->workspaceName) {
            WorkspaceName::forLive() => HierarchyRelationViewStatement::for($this->workspaceName, $this->tableNames),
            default => HierarchyRelationStatement::for($this->tableNames),
        };
        $this->nodeQueryBuilder = new NodeQueryBuilder($this->dbal, $this->tableNames, $this->hierarchyRelationStatement);
    }

    public function getContentRepositoryId(): ContentRepositoryId
    {
        return $this->contentRepositoryId;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getSubgraph(
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        return new ContentSubgraph(
            $this->contentRepositoryId,
            $this->workspaceName,
            $this->contentStreamLayers,
            $dimensionSpacePoint,
            $visibilityConstraints,
            $this->dbal,
            $this->nodeFactory,
            $this->nodeTypeManager,
            $this->tableNames,
            $this->hierarchyRelationStatement,
        );
    }

    public function findRootNodeAggregateByType(
        NodeTypeName $nodeTypeName
    ): ?NodeAggregate {
        $rootNodeAggregates = $this->findRootNodeAggregates(
            FindRootNodeAggregatesFilter::create(nodeTypeName: $nodeTypeName)
        );

        if ($rootNodeAggregates->count() > 1) {
            // todo drop this check as this is enforced by the write side? https://github.com/neos/neos-development-collection/pull/4339
            $ids = [];
            foreach ($rootNodeAggregates as $rootNodeAggregate) {
                $ids[] = $rootNodeAggregate->nodeAggregateId->value;
            }

            // We throw if multiple root node aggregates of the given $nodeTypeName were found,
            // as this would lead to nondeterministic results. Must not happen.
            throw new \RuntimeException(sprintf(
                'More than one root node aggregate of type "%s" found (IDs: %s).',
                $nodeTypeName->value,
                implode(', ', $ids)
            ));
        }

        return $rootNodeAggregates->first();
    }

    public function findRootNodeAggregates(
        FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates {
        $rootNodeAggregateQueryBuilder = $this->nodeQueryBuilder->buildFindRootNodeAggregatesQuery($this->contentStreamLayers, $filter);
        return $this->mapQueryBuilderToNodeAggregates($rootNodeAggregateQueryBuilder);
    }

    public function findNodeAggregatesByType(
        NodeTypeName $nodeTypeName
    ): NodeAggregates {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery();
        $queryBuilder
            ->andWhere('n.nodetypename = :nodeTypeName')
            ->setParameters([
                'contentStreamLayers' => $this->contentStreamLayers->toIntArray(),
                'nodeTypeName' => $nodeTypeName->value,
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
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
                'contentStreamLayers' => $this->contentStreamLayers->toIntArray()
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER
            ]);

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $this->fetchRows($queryBuilder),
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findNodeAggregatesByIds(
        NodeAggregateIds $nodeAggregateIds
    ): NodeAggregates {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery()
            ->andWhere('n.nodeaggregateid in (:nodeAggregateIds)')
            ->orderBy('n.relationanchorpoint', 'DESC')
            ->setParameters([
                'nodeAggregateIds' => $nodeAggregateIds->toStringArray(),
                'contentStreamLayers' => $this->contentStreamLayers->toIntArray()
            ], [
                'nodeAggregateIds' => ArrayParameterType::STRING,
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    /**
     * Parent node aggregates can have a greater dimension space coverage than the given child.
     * Thus, it is not enough to just resolve them from the nodes and edges connected to the given child node aggregate.
     * Instead, we resolve all parent node aggregate ids instead and fetch the complete aggregates from there.
     */
    public function findParentNodeAggregates(
        NodeAggregateId $childNodeAggregateId
    ): NodeAggregates {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery()
            ->innerJoin('n', $this->hierarchyRelationStatement->toSql(), 'ch', 'ch.parentnodeanchor = n.relationanchorpoint')
            ->innerJoin('ch', $this->nodeQueryBuilder->tableNames->node(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->andWhere('cn.nodeaggregateid = :nodeAggregateId')
            ->setParameters([
                'nodeAggregateId' => $childNodeAggregateId->value,
                'contentStreamLayers' => $this->contentStreamLayers->toIntArray()
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findAncestorNodeAggregateIds(NodeAggregateId $entryNodeAggregateId): NodeAggregateIds
    {
        $queryBuilderInitial = $this->createQueryBuilder()
            ->select('ch.parentnodeanchor')
            ->from($this->hierarchyRelationStatement->toSql(), 'ch')
            ->innerJoin('ch', $this->nodeQueryBuilder->tableNames->node(), 'c', 'c.relationanchorpoint = ch.childnodeanchor')
            ->andWhere('c.nodeaggregateid = :entryNodeAggregateId');

        $queryBuilderRecursive = $this->createQueryBuilder()
            ->select('ph.parentnodeanchor')
            ->from('ancestry', 'ch')
            ->innerJoin('ch', $this->hierarchyRelationStatement->toSql(), 'ph', 'ph.childnodeanchor = ch.parentnodeanchor');

        $queryBuilderCte = $this->createQueryBuilder()
            ->select('n.nodeAggregateId')
            ->from('ancestry', 'a')
            ->innerJoin('a', $this->nodeQueryBuilder->tableNames->node(), 'n', 'n.relationanchorpoint = a.parentnodeanchor')
            ->setParameter('contentStreamLayers', $this->contentStreamLayers->toIntArray(), ArrayParameterType::STRING)
            ->setParameter('entryNodeAggregateId', $entryNodeAggregateId->value);

        $nodeAggregateIdRows = $this->fetchCteResults(
            $queryBuilderInitial,
            $queryBuilderRecursive,
            $queryBuilderCte,
            'ancestry'
        );

        return NodeAggregateIds::fromArray(array_map(fn(array $row) => NodeAggregateId::fromString($row['nodeAggregateId']), $nodeAggregateIdRows));
    }

    public function findChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): NodeAggregates {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->contentStreamLayers);
        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(NodeAggregateId $childNodeAggregateId, OriginDimensionSpacePoint $childOriginDimensionSpacePoint): ?NodeAggregate
    {
        $subQueryBuilder = $this->createQueryBuilder()
            ->select('pn.nodeaggregateid')
            ->from($this->nodeQueryBuilder->tableNames->node(), 'pn')
            ->innerJoin('pn', $this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :childOriginDimensionSpacePointHash')->toSql(), 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->nodeQueryBuilder->tableNames->node(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->where('cn.nodeaggregateid = :childNodeAggregateId')
            ->andWhere('cn.origindimensionspacepointhash = :childOriginDimensionSpacePointHash');

        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.subtreetags, dsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->nodeQueryBuilder->tableNames->node(), 'n')
            ->innerJoin('n', $this->hierarchyRelationStatement->toSql(), 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->innerJoin('h', $this->nodeQueryBuilder->tableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->where('n.nodeaggregateid = (' . $subQueryBuilder->getSQL() . ')')
            ->setParameters([
                'contentStreamLayers' => $this->contentStreamLayers->toIntArray(),
                'childNodeAggregateId' => $childNodeAggregateId->value,
                'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->hash,
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $this->fetchRows($queryBuilder),
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findTetheredChildNodeAggregates(NodeAggregateId $parentNodeAggregateId): NodeAggregates
    {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->contentStreamLayers)
            ->andWhere('cn.classification = :tetheredClassification')
            ->setParameter('tetheredClassification', NodeAggregateClassification::CLASSIFICATION_TETHERED->value);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findChildNodeAggregateByName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): ?NodeAggregate {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->contentStreamLayers)
            ->andWhere('cn.name = :relationName')
            ->setParameter('relationName', $name->value);

        return $this->mapQueryBuilderToNodeAggregate($queryBuilder);
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(NodeName $nodeName, NodeAggregateId $parentNodeAggregateId, OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint, DimensionSpacePointSet $dimensionSpacePointsToCheck): DimensionSpacePointSet
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('dsp.dimensionspacepoint, h.dimensionspacepointhash')
            ->from($this->hierarchyRelationStatement->where('h.dimensionspacepointhash IN (:dimensionSpacePointHashes)')->toSql(), 'h')
            ->innerJoin('h', $this->nodeQueryBuilder->tableNames->node(), 'n', 'n.relationanchorpoint = h.parentnodeanchor')
            ->innerJoin('h', $this->nodeQueryBuilder->tableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->innerJoin('n', $this->hierarchyRelationStatement->toSql(), 'ph', 'ph.childnodeanchor = n.relationanchorpoint')
            ->where('n.nodeaggregateid = :parentNodeAggregateId')
            ->andWhere('n.origindimensionspacepointhash = :parentNodeOriginDimensionSpacePointHash')
            ->andWhere('n.name = :nodeName')
            ->setParameters([
                'parentNodeAggregateId' => $parentNodeAggregateId->value,
                'parentNodeOriginDimensionSpacePointHash' => $parentNodeOriginDimensionSpacePoint->hash,
                'contentStreamLayers' => $this->contentStreamLayers->toIntArray(),
                'dimensionSpacePointHashes' => $dimensionSpacePointsToCheck->getPointHashes(),
                'nodeName' => $nodeName->value
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);
        $dimensionSpacePoints = [];
        foreach ($this->fetchRows($queryBuilder) as $hierarchyRelationData) {
            $dimensionSpacePoints[$hierarchyRelationData['dimensionspacepointhash']] = DimensionSpacePoint::fromJsonString($hierarchyRelationData['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    public function findNodeAggregatesTaggedBy(SubtreeTag $subtreeTag): NodeAggregates
    {
        $queryBuilder =  $this->createQueryBuilder()
            ->select('n.*, h.subtreetags, dsp.dimensionspacepoint AS covereddimensionspacepoint')
            // select the subtree tags from tagged (t) h and then join h again to fetch all node rows in that aggregate
            ->from($this->hierarchyRelationStatement->where('JSON_EXTRACT(h.subtreetags, :tagPath) LIKE "true"')->toSql(), 'th')
            ->innerJoin('th', $this->hierarchyRelationStatement->toSql(), 'h', 'th.childnodeanchor = h.childnodeanchor')
            ->innerJoin('h', $this->tableNames->node(), 'n', 'h.childnodeanchor = n.relationanchorpoint')
            ->innerJoin('h', $this->tableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->orderBy('n.relationanchorpoint', 'DESC')
            ->setParameters([
                'tagPath' => '$."' . $subtreeTag->value . '"',
                'contentStreamLayers' => $this->contentStreamLayers->toIntArray()
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findUsedNodeTypeNames(): NodeTypeNames
    {
        return NodeTypeNames::fromArray(array_map(
            static fn (array $row) => NodeTypeName::fromString($row['nodetypename']),
            $this->fetchRows($this->nodeQueryBuilder->buildFindUsedNodeTypeNamesQuery())
        ));
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->dbal->createQueryBuilder();
    }

    private function mapQueryBuilderToNodeAggregate(QueryBuilder $queryBuilder): ?NodeAggregate
    {
        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $this->fetchRows($queryBuilder),
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return NodeAggregates
     */
    private function mapQueryBuilderToNodeAggregates(QueryBuilder $queryBuilder): NodeAggregates
    {
        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $this->fetchRows($queryBuilder),
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchRows(QueryBuilder $queryBuilder): array
    {
        try {
            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch rows from database: %s', $e->getMessage()), 1701444358, $e);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchCteResults(QueryBuilder $queryBuilderInitial, QueryBuilder $queryBuilderRecursive, QueryBuilder $queryBuilderCte, string $cteTableName = 'cte'): array
    {
        $query = <<<SQL
            WITH RECURSIVE {$cteTableName} AS (
                {$queryBuilderInitial->getSQL()}
                UNION
                {$queryBuilderRecursive->getSQL()}
            )
            {$queryBuilderCte->getSQL()}
        SQL;
        $parameters = array_merge($queryBuilderInitial->getParameters(), $queryBuilderRecursive->getParameters(), $queryBuilderCte->getParameters());
        $parameterTypes = array_merge($queryBuilderInitial->getParameterTypes(), $queryBuilderRecursive->getParameterTypes(), $queryBuilderCte->getParameterTypes());
        try {
            return $this->dbal->fetchAllAssociative($query, $parameters, $parameterTypes);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch CTE result: %s', $e->getMessage()), 1678358108, $e);
        }
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }
}
