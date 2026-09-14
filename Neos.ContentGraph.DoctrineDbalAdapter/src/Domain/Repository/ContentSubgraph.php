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
use Doctrine\DBAL\Result;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeSortPath;
use Neos\ContentGraph\DoctrineDbalAdapter\HierarchyRelationSubquery;
use Neos\ContentGraph\DoctrineDbalAdapter\NodeAggregateIdCondition;
use Neos\ContentGraph\DoctrineDbalAdapter\NodeQueryBuilder;
use Neos\ContentGraph\DoctrineDbalAdapter\ReferenceDestinationNodeAggregateIdCondition;
use Neos\ContentGraph\DoctrineDbalAdapter\SqlTableSubqueryFactory;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\ExpandedNodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\OrderingDirection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\TimestampField;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtrees;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Dbal\Query\Parameter;
use Neos\ContentRepository\Dbal\Query\Parameters;
use Neos\ContentRepository\Dbal\Query\QueryBuilder;
use Neos\ContentRepository\Dbal\Query\StaticWhereCondition;

/**
 * The content subgraph application repository
 *
 * To be used as a read-only source of nodes.
 *
 * ## Conventions for SQL queries
 *
 * - n -> node
 * - h -> hierarchy edge
 * - r -> reference
 *
 * - if more than one node (parent-child)
 *   - pn -> parent node
 *   - cn -> child node
 *   - h -> the hierarchy edge connecting parent and child
 *   - ph -> the hierarchy edge incoming to the parent (sometimes relevant)
 *   - ch -> the hierarchy edge of the child (sometimes relevant)
 *
 *  - if more than one node (source-destination)
 *   - sn -> source node
 *   - dn -> destination node
 *   - sh -> the hierarchy edge for the source node
 *   - dh -> the hierarchy edge for the destination node
 *
 *
 * @internal the parent {@see ContentSubgraphInterface} is API
 */
final class ContentSubgraph implements ContentSubgraphInterface
{
    /**
     * Orders ancestors by distance: the longer the sort path, the deeper - and therefore closer - the node.
     * The prefixes of one path all have distinct lengths, so this is exact and reproduces the `level`
     * ordering of the recursive CTE it replaced.
     */
    private const ANCESTOR_ORDERING_EXPRESSION = 'LENGTH(h.sortpath)';

    private readonly NodeQueryBuilder $nodeQueryBuilder;

    /**
     * Hierarchy relations for a subgraph - filtered by content stream and dimension space point
     */
    private readonly HierarchyRelationSubquery $hierarchyRelationQuery;

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly WorkspaceName $workspaceName,
        private readonly ContentStreamLayers $contentStreamLayers,
        private readonly DimensionSpacePoint $dimensionSpacePoint,
        private readonly VisibilityConstraints $visibilityConstraints,
        private readonly Connection $dbal,
        private readonly NodeFactory $nodeFactory,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly ContentGraphTableNames $tableNames
    ) {
        $this->nodeQueryBuilder = new NodeQueryBuilder($this->dbal, $tableNames);
        $this->hierarchyRelationQuery = SqlTableSubqueryFactory::for($tableNames)
            ->forHierarchyRelation($this->contentStreamLayers)
            ->withDimensionSpacePoint($this->dimensionSpacePoint);
    }

    public function getContentRepositoryId(): ContentRepositoryId
    {
        return $this->contentRepositoryId;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function getVisibilityConstraints(): VisibilityConstraints
    {
        return $this->visibilityConstraints;
    }

    public function findChildNodes(NodeAggregateId $parentNodeAggregateId, FindChildNodesFilter $filter): Nodes
    {
        $queryBuilder = $this->buildChildNodesQuery($parentNodeAggregateId, $filter);
        if ($filter->pagination !== null) {
            $this->applyPagination($queryBuilder, $filter->pagination);
        }
        if ($filter->ordering !== null) {
            $this->applyOrdering($queryBuilder, $filter->ordering);
        }
        $queryBuilder->addOrderBy('h.sortpath');
        return $this->fetchNodes($queryBuilder);
    }

    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, CountChildNodesFilter $filter): int
    {
        $queryBuilder = $this->buildChildNodesQuery($parentNodeAggregateId, $filter);
        return $this->fetchCount($queryBuilder);
    }

    public function findReferences(NodeAggregateId $nodeAggregateId, FindReferencesFilter $filter): References
    {
        $queryBuilder = $this->buildReferencesQuery($nodeAggregateId, $filter);
        return $this->fetchReferences($queryBuilder);
    }

    public function countReferences(NodeAggregateId $nodeAggregateId, CountReferencesFilter $filter): int
    {
        return $this->fetchCount($this->buildReferencesQuery($nodeAggregateId, $filter));
    }

    public function findBackReferences(NodeAggregateId $nodeAggregateId, FindBackReferencesFilter $filter): References
    {
        $queryBuilder = $this->buildBackreferencesQuery($nodeAggregateId, $filter);
        return $this->fetchReferences($queryBuilder);
    }

    public function countBackReferences(NodeAggregateId $nodeAggregateId, CountBackReferencesFilter $filter): int
    {
        return $this->fetchCount($this->buildBackreferencesQuery($nodeAggregateId, $filter));
    }

    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($nodeAggregateId);
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeQuery($this->hierarchyRelationQuery->withPossibleChildNodeAggregateId($nodeAggregateIdCondition))
            ->whereCondition('n', $nodeAggregateIdCondition);

        $this->addSubtreeTagConstraints($queryBuilder);
        return $this->fetchNode($queryBuilder);
    }

    public function findNodesByIds(NodeAggregateIds $nodeAggregateIds): Nodes
    {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateIds($nodeAggregateIds);
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeQuery($this->hierarchyRelationQuery->withPossibleChildNodeAggregateId($nodeAggregateIdCondition))
            ->whereCondition('n', $nodeAggregateIdCondition);

        $this->addSubtreeTagConstraints($queryBuilder);
        return $this->fetchNodes($queryBuilder);
    }

    public function findRootNodeByType(NodeTypeName $nodeTypeName): ?Node
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeQuery($this->hierarchyRelationQuery->withParentNodeRelationAnchor(
            NodeRelationAnchorPoint::forRootEdge()
        ))
            ->andWhere('n.nodetypename = :nodeTypeName')->setParameter('nodeTypeName', $nodeTypeName->value);
        $this->addSubtreeTagConstraints($queryBuilder);
        return $this->fetchNode($queryBuilder);
    }

    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicParentNodeQuery($this->hierarchyRelationQuery, $childNodeAggregateId);
        $this->addSubtreeTagConstraints($queryBuilder, 'ph');
        return $this->fetchNode($queryBuilder);
    }

    public function findNodeByPath(NodePath|NodeName $path, NodeAggregateId $startingNodeAggregateId): ?Node
    {
        $path = $path instanceof NodeName ? NodePath::fromNodeNames($path) : $path;

        $startingNode = $this->findNodeById($startingNodeAggregateId);

        return $startingNode
            ? $this->findNodeByPathFromStartingNode($path, $startingNode)
            : null;
    }

    public function findNodeByAbsolutePath(AbsoluteNodePath $path): ?Node
    {
        $startingNode = $this->findRootNodeByType($path->rootNodeTypeName);

        return $startingNode
            ? $this->findNodeByPathFromStartingNode($path->path, $startingNode)
            : null;
    }

    /**
     * Find a single child node by its name
     *
     * @return Node|null the node that is connected to its parent with the specified $nodeName, or NULL if no matching node exists or the parent node is not accessible
     */
    private function findChildNodeConnectedThroughEdgeName(NodeAggregateId $parentNodeAggregateId, NodeName $nodeName): ?Node
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicChildNodesQuery($this->hierarchyRelationQuery, $parentNodeAggregateId)
            ->andWhere('n.name = :edgeName')->setParameter('edgeName', $nodeName->value);
        $this->addSubtreeTagConstraints($queryBuilder);
        return $this->fetchNode($queryBuilder);
    }

    public function findSucceedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, FindSucceedingSiblingNodesFilter $filter): Nodes
    {
        $queryBuilder = $this->buildSiblingsQuery(false, $siblingNodeAggregateId, $filter);
        return $this->fetchNodes($queryBuilder);
    }

    public function findPrecedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, FindPrecedingSiblingNodesFilter $filter): Nodes
    {
        $queryBuilder = $this->buildSiblingsQuery(true, $siblingNodeAggregateId, $filter);
        return $this->fetchNodes($queryBuilder);
    }

    public function retrieveNodePath(NodeAggregateId $nodeAggregateId): AbsoluteNodePath
    {
        $leafNode = $this->findNodeById($nodeAggregateId);
        if (!$leafNode) {
            throw new \InvalidArgumentException(
                'Failed to retrieve node path for node "' . $nodeAggregateId->value . '"',
                1687513836
            );
        }
        $ancestors = $this->findAncestorNodes($leafNode->aggregateId, FindAncestorNodesFilter::create())
            ->reverse();

        try {
            return AbsoluteNodePath::fromLeafNodeAndAncestors($leafNode, $ancestors);
        } catch (\InvalidArgumentException $exception) {
            throw new \InvalidArgumentException(
                'Failed to retrieve node path for node "' . $nodeAggregateId->value . '"',
                1687513836,
                $exception
            );
        }
    }

    /**
     * The whole subtree is the contiguous sort path range below the entry node {@see NodeSortPath}, read in one
     * range scan and re-nested in PHP.
     *
     * This is the one traversal where the recursive CTE genuinely *pruned*: `nodeTypes` and `maximumLevels`
     * were applied in the recursive step, so a non-matching node took its whole subtree with it. Filtering the
     * flat range row-wise is nevertheless equivalent, because the re-nesting below only ever attaches a node to
     * a parent it actually finds: a node whose parent was filtered out is never reachable from the entry node
     * and is silently dropped. A node therefore survives iff every node between it and the entry survives -
     * which is what pruning means.
     *
     * The entry node itself is fetched separately because it was never subject to the `nodeTypes` filter: the
     * CTE applied that filter only to the recursive step, never to its anchor.
     */
    public function findSubtree(NodeAggregateId $entryNodeAggregateId, FindSubtreeFilter $filter): ?Subtree
    {
        $entryNode = $this->findNodeById($entryNodeAggregateId);
        $entrySortPath = $this->fetchSortPath($entryNodeAggregateId);
        if ($entryNode === null || $entrySortPath === null) {
            return null;
        }

        // Depth relative to the entry node: every level below it adds exactly one separator to the sort path.
        $relativeLevelExpression = sprintf(
            "(LENGTH(h.sortpath) - LENGTH(REPLACE(h.sortpath, '%s', '')) - %d)",
            NodeSortPath::SEPARATOR,
            $entrySortPath->depth() - 1
        );

        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeQuery(
            $this->hierarchyRelationQuery->withWhereCondition(self::descendantRangeCondition($entrySortPath)),
            'n',
            'n.*, h.subtreetags, p.nodeaggregateid AS parentNodeAggregateId, ' . $relativeLevelExpression . ' AS level'
        )
            // every row here is strictly below the entry node, so its parent relation always exists
            ->innerJoin('h', $this->tableNames->node(), 'p', 'p.relationanchorpoint = h.parentnodeanchor')
            // the sort path already encodes depth-first document order, so no secondary ordering is needed
            ->orderBy('h.sortpath');
        $this->addSubtreeTagConstraints($queryBuilder);

        if ($filter->maximumLevels !== null) {
            // the CTE stopped recursing once the *parent* reached the limit, which includes children at exactly
            // $maximumLevels - hence "<=" and not "<"
            $queryBuilder->andWhere($relativeLevelExpression . ' <= :maximumLevels')->setParameter('maximumLevels', $filter->maximumLevels);
        }
        if ($filter->nodeTypes !== null) {
            $this->nodeQueryBuilder->addNodeTypeCriteria($queryBuilder, ExpandedNodeTypeCriteria::create($filter->nodeTypes, $this->nodeTypeManager));
        }

        try {
            $nodeRows = $this->executeQuery($queryBuilder)->fetchAllAssociative();
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch subtree of node "%s": %s', $entryNodeAggregateId->value, $e->getMessage()), 1782600002, $e);
        }

        /** @var array<string, Subtree[]> $subtreesByParentNodeId */
        $subtreesByParentNodeId = [];
        // reverse document order guarantees that all descendants of a node are built before the node itself
        foreach (array_reverse($nodeRows) as $nodeData) {
            $nodeAggregateId = $nodeData['nodeaggregateid'];
            $parentNodeAggregateId = $nodeData['parentNodeAggregateId'];
            $subtree = Subtree::create(
                (int)$nodeData['level'],
                $this->nodeFactory->mapNodeRowToNode(
                    $nodeData,
                    $this->workspaceName,
                    $this->dimensionSpacePoint,
                    $this->visibilityConstraints
                ),
                array_key_exists($nodeAggregateId, $subtreesByParentNodeId) ? Subtrees::fromArray(array_reverse($subtreesByParentNodeId[$nodeAggregateId])) : Subtrees::createEmpty()
            );
            if (!array_key_exists($parentNodeAggregateId, $subtreesByParentNodeId)) {
                $subtreesByParentNodeId[$parentNodeAggregateId] = [];
            }
            $subtreesByParentNodeId[$parentNodeAggregateId][] = $subtree;
        }

        return Subtree::create(
            0,
            $entryNode,
            array_key_exists($entryNodeAggregateId->value, $subtreesByParentNodeId) ? Subtrees::fromArray(array_reverse($subtreesByParentNodeId[$entryNodeAggregateId->value])) : Subtrees::createEmpty()
        );
    }

    public function findAncestorNodes(NodeAggregateId $entryNodeAggregateId, FindAncestorNodesFilter $filter): Nodes
    {
        $queryBuilder = $this->buildAncestorNodesQuery($entryNodeAggregateId, $filter);
        if ($queryBuilder === null) {
            return Nodes::createEmpty();
        }
        $queryBuilder->addOrderBy(self::ANCESTOR_ORDERING_EXPRESSION, 'DESC');
        return $this->fetchNodes($queryBuilder);
    }

    public function countAncestorNodes(NodeAggregateId $entryNodeAggregateId, CountAncestorNodesFilter $filter): int
    {
        $queryBuilder = $this->buildAncestorNodesQuery($entryNodeAggregateId, $filter);
        if ($queryBuilder === null) {
            return 0;
        }
        return $this->fetchCount($queryBuilder);
    }

    public function findClosestNode(NodeAggregateId $entryNodeAggregateId, FindClosestNodeFilter $filter): ?Node
    {
        $queryBuilder = $this->buildAncestorNodesQuery($entryNodeAggregateId, $filter);
        if ($queryBuilder === null) {
            return null;
        }
        // "closest" is the longest of the candidate paths that survived the node type filter. The CTE version
        // had no ORDER BY at all here and relied on the order in which the recursion emitted its rows.
        $queryBuilder->addOrderBy(self::ANCESTOR_ORDERING_EXPRESSION, 'DESC')->setMaxResults(1);
        return $this->fetchNode($queryBuilder);
    }

    public function findDescendantNodes(NodeAggregateId $entryNodeAggregateId, FindDescendantNodesFilter $filter): Nodes
    {
        $queryBuilder = $this->buildDescendantNodesQuery($entryNodeAggregateId, $filter);
        if ($queryBuilder === null) {
            return Nodes::createEmpty();
        }
        if ($filter->ordering !== null) {
            $this->applyOrdering($queryBuilder, $filter->ordering);
        }
        if ($filter->pagination !== null) {
            $this->applyPagination($queryBuilder, $filter->pagination);
        }
        $queryBuilder->addOrderBy('h.sortpath');
        return $this->fetchNodes($queryBuilder);
    }

    public function countDescendantNodes(NodeAggregateId $entryNodeAggregateId, CountDescendantNodesFilter $filter): int
    {
        $queryBuilder = $this->buildDescendantNodesQuery($entryNodeAggregateId, $filter);
        if ($queryBuilder === null) {
            return 0;
        }
        return $this->fetchCount($queryBuilder);
    }

    public function countNodes(): int
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeQuery($this->hierarchyRelationQuery, 'n', 'COUNT(*)');
        try {
            $result = $this->executeQuery($queryBuilder)->fetchOne();
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to count all nodes: %s', $e->getMessage()), 1678364741, $e);
        }

        if (!is_int($result)) {
            throw new \RuntimeException(sprintf('Expected result to be of type integer but got: %s', get_debug_type($result)), 1678366902);
        }

        return $result;
    }

    /** ------------------------------------------- */

    private function findNodeByPathFromStartingNode(NodePath $path, Node $startingNode): ?Node
    {
        $currentNode = $startingNode;

        foreach ($path->getParts() as $edgeName) {
            $currentNode = $this->findChildNodeConnectedThroughEdgeName($currentNode->aggregateId, $edgeName);
            if ($currentNode === null) {
                return null;
            }
        }
        return $currentNode;
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return QueryBuilder::createForConnection($this->dbal);
    }

    /**
     * The materialised sort path of a node within this subgraph, or NULL if the node is not part of it.
     *
     * This is the anchor of every range based traversal {@see NodeSortPath}: a node's descendants are the
     * contiguous sort path range below it, its ancestors are its proper prefixes. One point lookup buys
     * that, which is what makes the recursive CTEs unnecessary.
     *
     * A removed node resolves to a tombstone row whose `sortpath` is NULL, so it correctly yields NULL here
     * and callers short-circuit to an empty result. The same holds for a node hidden by the visibility
     * constraints, which is what the previous recursive CTEs enforced in their anchor queries.
     */
    private function fetchSortPath(NodeAggregateId $nodeAggregateId): ?NodeSortPath
    {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($nodeAggregateId);

        $queryBuilder = $this->createQueryBuilder()
            ->select('h.sortpath')
            ->fromTableSubquery($this->hierarchyRelationQuery->withPossibleChildNodeAggregateId($nodeAggregateIdCondition), 'h')
            ->innerJoin('h', $this->tableNames->node(), 'n', 'n.relationanchorpoint = h.childnodeanchor')
            ->whereCondition('n', $nodeAggregateIdCondition)
            ->setMaxResults(1);
        $this->addSubtreeTagConstraints($queryBuilder);

        try {
            $sortPath = $this->executeQuery($queryBuilder)->fetchOne();
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch sort path for node "%s": %s', $nodeAggregateId->value, $e->getMessage()), 1782600001, $e);
        }

        if (!is_string($sortPath) || $sortPath === '') {
            return null;
        }
        return NodeSortPath::fromString($sortPath);
    }

    private function addSubtreeTagConstraints(QueryBuilder $queryBuilder, string $hierarchyRelationTableAlias = 'h'): void
    {
        $hierarchyRelationTablePrefix = $hierarchyRelationTableAlias === '' ? '' : $hierarchyRelationTableAlias . '.';
        $i = 0;
        foreach ($this->visibilityConstraints->excludedSubtreeTags as $excludedTag) {
            $queryBuilder->andWhere('NOT JSON_CONTAINS_PATH(' . $hierarchyRelationTablePrefix . 'subtreetags, \'one\', :tagPath' . $i . ')')->setParameter('tagPath' . $i, '$."' . $excludedTag->value . '"');
            $i++;
        }
    }

    private function buildChildNodesQuery(NodeAggregateId $parentNodeAggregateId, FindChildNodesFilter|CountChildNodesFilter $filter): QueryBuilder
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicChildNodesQuery($this->hierarchyRelationQuery, $parentNodeAggregateId);
        if ($filter->nodeTypes !== null) {
            $this->nodeQueryBuilder->addNodeTypeCriteria($queryBuilder, ExpandedNodeTypeCriteria::create($filter->nodeTypes, $this->nodeTypeManager));
        }
        if ($filter->searchTerm !== null) {
            $this->nodeQueryBuilder->addSearchTermConstraints($queryBuilder, $filter->searchTerm);
        }
        if ($filter->propertyValue !== null) {
            $this->nodeQueryBuilder->addPropertyValueConstraints($queryBuilder, $filter->propertyValue);
        }
        $this->addSubtreeTagConstraints($queryBuilder);
        return $queryBuilder;
    }

    private function buildReferencesQuery(NodeAggregateId $nodeAggregateId, FindReferencesFilter|CountReferencesFilter $filter): QueryBuilder
    {
        $subselectParameters = [];
        $subtreeTagConstraints = '';
        $i = 0;
        // FIXME centralise subtree tag constraint building in a single point e.g ContentSubgraph::addSubtreeTagConstraints() - uses query builder
        foreach ($this->visibilityConstraints->excludedSubtreeTags as $excludedTag) {
            $subtreeTagConstraints .= ' AND NOT JSON_CONTAINS_PATH(sh.subtreetags, \'one\', :sourceTagPath' . $i . ')';
            $subselectParameters[] = Parameter::string('sourceTagPath' . $i, '$."' . $excludedTag->value . '"');
            $i++;
        }
        $subselectParameters = Parameters::create(...$subselectParameters);

        $queryBuilder = $this->createQueryBuilder()
            ->select("dn.*, dh.subtreetags, r.name AS referencename, r.properties AS referenceproperties")
            ->fromTableSubquery($this->hierarchyRelationQuery, 'dh')
            ->innerJoin('dh', $this->tableNames->node(), 'dn', 'dn.relationanchorpoint = dh.childnodeanchor')
            ->innerJoin('dn', $this->tableNames->referenceRelation(), 'r', 'r.destinationnodeaggregateid = dn.nodeaggregateid')
            // FIXME evaluate to use NodeAggregateIdClause prefiltering here as well? Possibly makes the subquery redundant because results will be prefiltered.
            ->where('r.nodeanchorpoint = (
                SELECT relationanchorpoint FROM ' . $this->tableNames->node() . ' sn
                JOIN ' . $this->hierarchyRelationQuery->toSql() . ' sh ON sn.relationanchorpoint = sh.childnodeanchor
                WHERE sn.nodeaggregateid = :nodeAggregateId  '
                . $subtreeTagConstraints . '
            )')
            ->mergeParameters($this->hierarchyRelationQuery->getParameters())
            ->mergeParameters($subselectParameters)
            ->setParameter('nodeAggregateId', $nodeAggregateId->value);
        $this->addSubtreeTagConstraints($queryBuilder, 'dh');
        if ($filter->nodeTypes !== null) {
            $this->nodeQueryBuilder->addNodeTypeCriteria($queryBuilder, ExpandedNodeTypeCriteria::create($filter->nodeTypes, $this->nodeTypeManager), "dn");
        }
        if ($filter->nodeSearchTerm !== null) {
            $this->nodeQueryBuilder->addSearchTermConstraints($queryBuilder, $filter->nodeSearchTerm, "dn");
        }
        if ($filter->nodePropertyValue !== null) {
            $this->nodeQueryBuilder->addPropertyValueConstraints($queryBuilder, $filter->nodePropertyValue, "dn");
        }
        if ($filter->referenceSearchTerm !== null) {
            $this->nodeQueryBuilder->addSearchTermConstraints($queryBuilder, $filter->referenceSearchTerm, 'r');
        }
        if ($filter->referencePropertyValue !== null) {
            $this->nodeQueryBuilder->addPropertyValueConstraints($queryBuilder, $filter->referencePropertyValue, 'r');
        }
        if ($filter->referenceName !== null) {
            $queryBuilder->andWhere('r.name = :referenceName')->setParameter('referenceName', $filter->referenceName->value);
        }
        if ($filter instanceof FindReferencesFilter) {
            if ($filter->ordering !== null) {
                $this->applyOrdering($queryBuilder, $filter->ordering, "dn");
            } elseif ($filter->referenceName === null) {
                $queryBuilder->addOrderBy('r.name');
            }
            $queryBuilder->addOrderBy('r.position');
            $queryBuilder->addOrderBy('dn.nodeaggregateid');
            if ($filter->pagination !== null) {
                $this->applyPagination($queryBuilder, $filter->pagination);
            }
        }
        return $queryBuilder;
    }

    private function buildBackreferencesQuery(NodeAggregateId $nodeAggregateId, FindBackReferencesFilter|CountBackReferencesFilter $filter): QueryBuilder
    {
        $subselectParameters = [];
        $subtreeTagConstraints = '';
        $i = 0;
        // FIXME centralise subtree tag constraint building in a single point e.g ContentSubgraph::addSubtreeTagConstraints() - uses query builder
        foreach ($this->visibilityConstraints->excludedSubtreeTags as $excludedTag) {
            $subtreeTagConstraints .= ' AND NOT JSON_CONTAINS_PATH(dh.subtreetags, \'one\', :destinationTagPath' . $i . ')';
            $subselectParameters[] = Parameter::string('destinationTagPath' . $i, '$."' . $excludedTag->value . '"');
            $i++;
        }
        $subselectParameters = Parameters::create(...$subselectParameters);

        $queryBuilder = $this->createQueryBuilder()
            ->select("sn.*, sh.subtreetags, r.name AS referencename, r.properties AS referenceproperties")
            ->fromTableSubquery($this->hierarchyRelationQuery->withPossibleChildNodeAggregateId(
                ReferenceDestinationNodeAggregateIdCondition::forNodeAggregateId($nodeAggregateId)
            ), 'sh')
            ->innerJoin('sh', $this->tableNames->node(), 'sn', 'sn.relationanchorpoint = sh.childnodeanchor')
            ->innerJoin('sn', $this->tableNames->referenceRelation(), 'r', 'r.nodeanchorpoint = sn.relationanchorpoint')
            // FIXME evaluate to use NodeAggregateIdClause prefiltering here as well? Possibly makes the subquery redundant because results will be prefiltered.
            ->where(<<<SQL
            r.destinationnodeaggregateid = (
              SELECT nodeaggregateid FROM {$this->tableNames->node()} dn
                JOIN {$this->hierarchyRelationQuery->toSql()} dh
                  ON dn.relationanchorpoint = dh.childnodeanchor
              WHERE dn.nodeaggregateid = :nodeAggregateId
                {$subtreeTagConstraints}
              LIMIT 1
            )
            SQL)
            ->mergeParameters($this->hierarchyRelationQuery->getParameters())
            ->mergeParameters($subselectParameters)
            ->setParameter('nodeAggregateId', $nodeAggregateId->value);
        $this->addSubtreeTagConstraints($queryBuilder, 'sh');
        if ($filter->nodeTypes !== null) {
            $this->nodeQueryBuilder->addNodeTypeCriteria($queryBuilder, ExpandedNodeTypeCriteria::create($filter->nodeTypes, $this->nodeTypeManager), "sn");
        }
        if ($filter->nodeSearchTerm !== null) {
            $this->nodeQueryBuilder->addSearchTermConstraints($queryBuilder, $filter->nodeSearchTerm, "sn");
        }
        if ($filter->nodePropertyValue !== null) {
            $this->nodeQueryBuilder->addPropertyValueConstraints($queryBuilder, $filter->nodePropertyValue, "sn");
        }
        if ($filter->referenceSearchTerm !== null) {
            $this->nodeQueryBuilder->addSearchTermConstraints($queryBuilder, $filter->referenceSearchTerm, 'r');
        }
        if ($filter->referencePropertyValue !== null) {
            $this->nodeQueryBuilder->addPropertyValueConstraints($queryBuilder, $filter->referencePropertyValue, 'r');
        }
        if ($filter->referenceName !== null) {
            $queryBuilder->andWhere('r.name = :referenceName')->setParameter('referenceName', $filter->referenceName->value);
        }
        if ($filter instanceof FindBackReferencesFilter) {
            if ($filter->ordering !== null) {
                $this->applyOrdering($queryBuilder, $filter->ordering, "sn");
            } elseif ($filter->referenceName === null) {
                $queryBuilder->addOrderBy('r.name');
            }
            $queryBuilder->addOrderBy('r.position');
            $queryBuilder->addOrderBy('sn.nodeaggregateid');
            if ($filter->pagination !== null) {
                $this->applyPagination($queryBuilder, $filter->pagination);
            }
        }
        return $queryBuilder;
    }

    private function buildSiblingsQuery(bool $preceding, NodeAggregateId $siblingNodeAggregateId, FindPrecedingSiblingNodesFilter|FindSucceedingSiblingNodesFilter $filter): QueryBuilder
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeSiblingsQuery($this->hierarchyRelationQuery, $preceding, $siblingNodeAggregateId);

        $this->addSubtreeTagConstraints($queryBuilder);
        if ($filter->nodeTypes !== null) {
            $this->nodeQueryBuilder->addNodeTypeCriteria($queryBuilder, ExpandedNodeTypeCriteria::create($filter->nodeTypes, $this->nodeTypeManager));
        }
        if ($filter->searchTerm !== null) {
            $this->nodeQueryBuilder->addSearchTermConstraints($queryBuilder, $filter->searchTerm);
        }
        if ($filter->propertyValue !== null) {
            $this->nodeQueryBuilder->addPropertyValueConstraints($queryBuilder, $filter->propertyValue);
        }
        if ($filter->pagination !== null) {
            $this->applyPagination($queryBuilder, $filter->pagination);
        }
        return $queryBuilder;
    }

    /**
     * A node's ancestors are exactly the proper prefixes of its own sort path
     * {@see NodeSortPath::ancestorPaths()}, so ancestor lookups are a literal `IN` list on an indexed column
     * rather than a recursive CTE. Sort paths are unique within a subgraph, so each prefix matches one row.
     *
     * {@see FindClosestNodeFilter} also considers the entry node itself, hence its own path is prepended in
     * that case.
     *
     * The subtree tag constraint stays row-wise, and could never have pruned here anyway: tags are inherited
     * *downwards*, so an excluded ancestor implies an excluded entry node, for which {@see fetchSortPath()}
     * already returns NULL.
     *
     * Returns NULL when there is nothing to look up - either the entry node is not visible in this subgraph,
     * or it is a root node and thus has no ancestors.
     */
    private function buildAncestorNodesQuery(NodeAggregateId $entryNodeAggregateId, FindAncestorNodesFilter|CountAncestorNodesFilter|FindClosestNodeFilter $filter): ?QueryBuilder
    {
        $entrySortPath = $this->fetchSortPath($entryNodeAggregateId);
        if ($entrySortPath === null) {
            return null;
        }
        $sortPaths = $entrySortPath->ancestorPaths();
        if ($filter instanceof FindClosestNodeFilter) {
            array_unshift($sortPaths, $entrySortPath->value);
        }
        if ($sortPaths === []) {
            return null;
        }

        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeQuery(
            $this->hierarchyRelationQuery->withWhereCondition(
                StaticWhereCondition::fromString(
                    'h',
                    'h.sortpath IN (:ancestorSortPaths)',
                    Parameters::create(Parameter::stringArray('ancestorSortPaths', $sortPaths))
                )
            ),
            'n',
            'n.*, h.subtreetags, h.sortpath'
        );
        $this->addSubtreeTagConstraints($queryBuilder);

        if ($filter->nodeTypes !== null) {
            $this->nodeQueryBuilder->addNodeTypeCriteria($queryBuilder, ExpandedNodeTypeCriteria::create($filter->nodeTypes, $this->nodeTypeManager));
        }
        return $queryBuilder;
    }

    /**
     * The descendants of a node are the contiguous sort path range below it {@see NodeSortPath}, so this is a
     * plain index range scan rather than a recursive CTE.
     *
     * Every filter is applied row-wise, which yields the same result the recursion did:
     *
     * - Subtree tags are materialised onto *every* descendant relation
     *   {@see SubtreeTagging::addSubtreeTag()}, so excluding a tagged row also excludes its whole subtree.
     * - Removing a node tombstones every relation of its subtree with a NULL `sortpath`
     *   {@see NodeRemoval::removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes()}, and NULL never
     *   satisfies the range predicate - no orphaned descendants can survive to be picked up here.
     * - `nodeTypes`, `searchTerm` and `propertyValue` were applied to the outer query even in the CTE version,
     *   never to the recursive step, so they never pruned in the first place.
     *
     * Returns NULL if the entry node is not part of this subgraph, in which case there are no descendants.
     */
    private function buildDescendantNodesQuery(NodeAggregateId $entryNodeAggregateId, FindDescendantNodesFilter|CountDescendantNodesFilter $filter): ?QueryBuilder
    {
        $entrySortPath = $this->fetchSortPath($entryNodeAggregateId);
        if ($entrySortPath === null) {
            return null;
        }

        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeQuery(
            $this->hierarchyRelationQuery->withWhereCondition(self::descendantRangeCondition($entrySortPath)),
            'n',
            'n.*, h.subtreetags, h.sortpath'
        );
        $this->addSubtreeTagConstraints($queryBuilder);

        if ($filter->nodeTypes !== null) {
            $this->nodeQueryBuilder->addNodeTypeCriteria($queryBuilder, ExpandedNodeTypeCriteria::create($filter->nodeTypes, $this->nodeTypeManager));
        }
        if ($filter->searchTerm !== null) {
            $this->nodeQueryBuilder->addSearchTermConstraints($queryBuilder, $filter->searchTerm);
        }
        if ($filter->propertyValue !== null) {
            $this->nodeQueryBuilder->addPropertyValueConstraints($queryBuilder, $filter->propertyValue);
        }
        return $queryBuilder;
    }

    /**
     * Restricts hierarchy relations to the descendants of the given path, excluding the node itself.
     *
     * This MUST be handed to {@see HierarchyRelationSubquery::withWhereCondition()} and never to
     * `withPossibleWhereCondition()`: `sortpath` is not layer invariant, so it must not reach the
     * `id IN (...)` prefilter {@see HierarchyRelationSubquery}.
     */
    private static function descendantRangeCondition(NodeSortPath $sortPath): StaticWhereCondition
    {
        return StaticWhereCondition::fromString(
            'h',
            'h.sortpath >= :descendantRangeStart AND h.sortpath < :descendantRangeEnd',
            Parameters::create(
                Parameter::string('descendantRangeStart', $sortPath->rangeStart()),
                Parameter::string('descendantRangeEnd', $sortPath->rangeEnd()),
            )
        );
    }

    private function applyOrdering(QueryBuilder $queryBuilder, Ordering $ordering, string $nodeTableAlias = 'n'): void
    {
        foreach ($ordering as $orderingField) {
            $order = match ($orderingField->direction) {
                OrderingDirection::ASCENDING => 'ASC',
                OrderingDirection::DESCENDING => 'DESC',
            };
            if ($orderingField->field instanceof PropertyName) {
                $queryBuilder->addOrderBy($this->nodeQueryBuilder->extractPropertyValue($orderingField->field, $nodeTableAlias), $order);
            } else {
                $timestampColumnName = match ($orderingField->field) {
                    TimestampField::CREATED => 'created',
                    TimestampField::ORIGINAL_CREATED => 'originalCreated',
                    TimestampField::LAST_MODIFIED => 'lastmodified',
                    TimestampField::ORIGINAL_LAST_MODIFIED => 'originallastmodified',
                };
                $queryBuilder->addOrderBy($nodeTableAlias . '.' . $timestampColumnName, $order);
            }
        }
    }

    private function applyPagination(QueryBuilder $queryBuilder, Pagination $pagination): void
    {
        $queryBuilder->setMaxResults($pagination->limit)->setFirstResult($pagination->offset);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return Result
     * @throws DBALException
     */
    private function executeQuery(QueryBuilder $queryBuilder): Result
    {
        return $queryBuilder->executeQuery();
    }

    private function fetchNode(QueryBuilder $queryBuilder): ?Node
    {
        try {
            $nodeRow = $this->executeQuery($queryBuilder)->fetchAssociative();
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch node: %s', $e->getMessage()), 1678286030, $e);
        }
        if ($nodeRow === false) {
            return null;
        }
        return $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->workspaceName,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints
        );
    }

    private function fetchNodes(QueryBuilder $queryBuilder): Nodes
    {
        try {
            $nodeRows = $this->executeQuery($queryBuilder)->fetchAllAssociative();
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch nodes: %s', $e->getMessage()), 1678292896, $e);
        }
        return $this->nodeFactory->mapNodeRowsToNodes(
            $nodeRows,
            $this->workspaceName,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints
        );
    }

    private function fetchCount(QueryBuilder $queryBuilder): int
    {
        try {
            return (int)$this->executeQuery($queryBuilder->select('COUNT(*)')->resetOrderBy()->setFirstResult(0)->setMaxResults(1))->fetchOne();
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch count: %s', $e->getMessage()), 1679048349, $e);
        }
    }

    private function fetchReferences(QueryBuilder $queryBuilder): References
    {
        try {
            $referenceRows = $this->executeQuery($queryBuilder)->fetchAllAssociative();
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch references: %s', $e->getMessage()), 1678364944, $e);
        }
        return $this->nodeFactory->mapReferenceRowsToReferences(
            $referenceRows,
            $this->workspaceName,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints
        );
    }
}
