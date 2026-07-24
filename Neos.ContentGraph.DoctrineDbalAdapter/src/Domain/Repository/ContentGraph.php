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
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\HierarchyRelationSubquery;
use Neos\ContentGraph\DoctrineDbalAdapter\NodeAggregateIdCondition;
use Neos\ContentGraph\DoctrineDbalAdapter\NodeQueryBuilder;
use Neos\ContentGraph\DoctrineDbalAdapter\SqlTableSubqueryFactory;
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
use Neos\ContentRepository\Dbal\Query\QueryBuilder;
use Neos\ContentRepository\Dbal\Query\StaticWhereCondition;

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

    /**
     * Hierarchy relations for a subgraph - filtered by content stream
     */
    private readonly HierarchyRelationSubquery $hierarchyRelationQuery;

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
        $this->nodeQueryBuilder = new NodeQueryBuilder($this->dbal, $this->tableNames);
        $this->hierarchyRelationQuery = SqlTableSubqueryFactory::for($this->tableNames)
            ->forHierarchyRelation($this->contentStreamLayers);
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
            $this->tableNames
        );
    }

    public function findRootNodeAggregateByType(
        NodeTypeName $nodeTypeName
    ): ?NodeAggregate {
        $rootNodeAggregates = $this->findRootNodeAggregates(
            FindRootNodeAggregatesFilter::create(nodeTypeName: $nodeTypeName)
        );

        return $rootNodeAggregates->first();
    }

    public function findRootNodeAggregates(
        FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates {
        $rootNodeAggregateQueryBuilder = $this->nodeQueryBuilder->buildFindRootNodeAggregatesQuery($this->hierarchyRelationQuery, $filter);
        return $this->mapQueryBuilderToNodeAggregates($rootNodeAggregateQueryBuilder);
    }

    public function findNodeAggregatesByType(
        NodeTypeName $nodeTypeName
    ): NodeAggregates {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery($this->hierarchyRelationQuery);
        $queryBuilder
            ->andWhere('n.nodetypename = :nodeTypeName')
            ->setParameter('nodeTypeName', $nodeTypeName->value);
        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findNodeAggregateById(
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($nodeAggregateId);
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery($this->hierarchyRelationQuery->withPossibleChildNodeAggregateId($nodeAggregateIdCondition))
            ->whereCondition('n', $nodeAggregateIdCondition);

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $this->fetchRows($queryBuilder),
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findNodeAggregatesByIds(
        NodeAggregateIds $nodeAggregateIds
    ): NodeAggregates {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateIds($nodeAggregateIds);
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery($this->hierarchyRelationQuery->withPossibleChildNodeAggregateId($nodeAggregateIdCondition))
            ->whereCondition('n', $nodeAggregateIdCondition);

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
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($childNodeAggregateId);
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery($this->hierarchyRelationQuery)
            ->innerJoinTableSubquery('n', $this->hierarchyRelationQuery->withPossibleChildNodeAggregateId($nodeAggregateIdCondition), 'ch', 'ch.parentnodeanchor = n.relationanchorpoint')
            ->innerJoin('ch', $this->tableNames->node(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->andWhereCondition($nodeAggregateIdCondition, 'cn');

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findAncestorNodeAggregateIds(NodeAggregateId $entryNodeAggregateId): NodeAggregateIds
    {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($entryNodeAggregateId);
        $queryBuilderInitial = $this->createQueryBuilder()
            ->select('ch.parentnodeanchor')
            ->fromTableSubquery($this->hierarchyRelationQuery->withPossibleChildNodeAggregateId($nodeAggregateIdCondition), 'ch')
            ->innerJoin('ch', $this->tableNames->node(), 'c', 'c.relationanchorpoint = ch.childnodeanchor')
            ->andWhereCondition($nodeAggregateIdCondition, 'c');

        $queryBuilderRecursive = $this->createQueryBuilder()
            ->select('ph.parentnodeanchor')
            ->from('ancestry', 'ch')
            ->innerJoinTableSubquery('ch', $this->hierarchyRelationQuery, 'ph', 'ph.childnodeanchor = ch.parentnodeanchor');

        $queryBuilderCte = $this->createQueryBuilder()
            ->select('n.nodeAggregateId')
            ->from('ancestry', 'a')
            ->innerJoin('a', $this->tableNames->node(), 'n', 'n.relationanchorpoint = a.parentnodeanchor');

        $nodeAggregateIdRows = $this->fetchCteResults(
            $queryBuilderInitial,
            $queryBuilderRecursive,
            $queryBuilderCte,
            'ancestry'
        );

        return NodeAggregateIds::fromArray(array_map(fn (array $row) => NodeAggregateId::fromString($row['nodeAggregateId']), $nodeAggregateIdRows));
    }

    public function findChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): NodeAggregates {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($this->hierarchyRelationQuery, $parentNodeAggregateId);
        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(NodeAggregateId $childNodeAggregateId, OriginDimensionSpacePoint $childOriginDimensionSpacePoint): ?NodeAggregate
    {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($childNodeAggregateId);

        $subQueryBuilder = $this->createQueryBuilder()
            ->select('pn.nodeaggregateid')
            ->from($this->tableNames->node(), 'pn')
            ->innerJoinTableSubquery('pn', $hierarchyRelationQuery = $this->hierarchyRelationQuery->withDimensionSpacePoint($childOriginDimensionSpacePoint->toDimensionSpacePoint())->withPossibleChildNodeAggregateId($nodeAggregateIdCondition), 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->tableNames->node(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->whereCondition('cn', $nodeAggregateIdCondition)
            ->andWhere('cn.origindimensionspacepointhash = ' . $hierarchyRelationQuery->getParameters()->getReference('dimensionSpacePointHash'));

        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.subtreetags, dsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->tableNames->node(), 'n')
            ->innerJoinTableSubquery('n', $this->hierarchyRelationQuery, 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->innerJoin('h', $this->tableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->where('n.nodeaggregateid = (' . $subQueryBuilder->getSQL() . ')')
            ->mergeParametersFromBuilder($subQueryBuilder);

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $this->fetchRows($queryBuilder),
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findTetheredChildNodeAggregates(NodeAggregateId $parentNodeAggregateId): NodeAggregates
    {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($this->hierarchyRelationQuery, $parentNodeAggregateId)
            ->andWhere('cn.classification = :tetheredClassification')
            ->setParameter('tetheredClassification', NodeAggregateClassification::CLASSIFICATION_TETHERED->value);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findChildNodeAggregateByName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): ?NodeAggregate {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($this->hierarchyRelationQuery, $parentNodeAggregateId)
            ->andWhere('cn.name = :relationName')
            ->setParameter('relationName', $name->value);

        return $this->mapQueryBuilderToNodeAggregate($queryBuilder);
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(NodeName $nodeName, NodeAggregateId $parentNodeAggregateId, OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint, DimensionSpacePointSet $dimensionSpacePointsToCheck): DimensionSpacePointSet
    {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($parentNodeAggregateId);

        $queryBuilder = $this->createQueryBuilder()
            ->select('dsp.dimensionspacepoint, h.dimensionspacepointhash')
            ->fromTableSubquery($this->hierarchyRelationQuery->withDimensionSpacePoints($dimensionSpacePointsToCheck), 'h')
            ->innerJoin('h', $this->tableNames->node(), 'n', 'n.relationanchorpoint = h.parentnodeanchor')
            ->innerJoin('h', $this->tableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->innerJoinTableSubquery('n', $this->hierarchyRelationQuery->withPossibleChildNodeAggregateId($nodeAggregateIdCondition), 'ph', 'ph.childnodeanchor = n.relationanchorpoint')
            ->whereCondition('n', $nodeAggregateIdCondition)
            ->andWhere('n.origindimensionspacepointhash = :parentNodeOriginDimensionSpacePointHash')
            ->andWhere('n.name = :nodeName')
            ->setParameter('parentNodeOriginDimensionSpacePointHash', $parentNodeOriginDimensionSpacePoint->hash)
            ->setParameter('nodeName', $nodeName->value);
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
            ->fromTableSubquery($this->hierarchyRelationQuery->withWhereCondition(StaticWhereCondition::fromString('h', 'JSON_EXTRACT(h.subtreetags, :tagPath) LIKE "true"')), 'th')
            ->innerJoinTableSubquery('th', $this->hierarchyRelationQuery, 'h', 'th.childnodeanchor = h.childnodeanchor')
            ->innerJoin('h', $this->tableNames->node(), 'n', 'h.childnodeanchor = n.relationanchorpoint')
            ->innerJoin('h', $this->tableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->setParameter('tagPath', '$."' . $subtreeTag->value . '"');

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
        return QueryBuilder::createForConnection($this->dbal);
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
