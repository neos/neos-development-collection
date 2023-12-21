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
use Doctrine\DBAL\Driver\Exception as DbalDriverException;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphIdentity;
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
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\OrderingDirection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\TimestampField;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\AndCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\NegateCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\OrCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueContains;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEndsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEquals;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueStartsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\ExpandedNodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

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
    private int $dynamicParameterCount = 0;

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly ContentStreamId $contentStreamId,
        private readonly DimensionSpacePoint $dimensionSpacePoint,
        private readonly VisibilityConstraints $visibilityConstraints,
        private readonly DbalClientInterface $client,
        private readonly NodeFactory $nodeFactory,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix
    ) {
    }

    public function getIdentity(): ContentSubgraphIdentity
    {
        return ContentSubgraphIdentity::create(
            $this->contentRepositoryId,
            $this->contentStreamId,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints
        );
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
        $queryBuilder->addOrderBy('h.position');
        return $this->fetchNodes($queryBuilder);
    }

    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, CountChildNodesFilter $filter): int
    {
        $queryBuilder = $this->buildChildNodesQuery($parentNodeAggregateId, $filter);
        return $this->fetchCount($queryBuilder);
    }

    public function findReferences(NodeAggregateId $nodeAggregateId, FindReferencesFilter $filter): References
    {
        $queryBuilder = $this->buildReferencesQuery(false, $nodeAggregateId, $filter);
        return $this->fetchReferences($queryBuilder);
    }

    public function countReferences(NodeAggregateId $nodeAggregateId, CountReferencesFilter $filter): int
    {
        return $this->fetchCount($this->buildReferencesQuery(false, $nodeAggregateId, $filter));
    }

    public function findBackReferences(NodeAggregateId $nodeAggregateId, FindBackReferencesFilter $filter): References
    {
        $queryBuilder = $this->buildReferencesQuery(true, $nodeAggregateId, $filter);
        return $this->fetchReferences($queryBuilder);
    }

    public function countBackReferences(NodeAggregateId $nodeAggregateId, CountBackReferencesFilter $filter): int
    {
        return $this->fetchCount($this->buildReferencesQuery(true, $nodeAggregateId, $filter));
    }

    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('n.nodeaggregateid = :nodeAggregateId')->setParameter('nodeAggregateId', $nodeAggregateId->value)
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash);
        $this->addRestrictionRelationConstraints($queryBuilder, 'n', 'h', ':contentStreamId', ':dimensionSpacePointHash');
        return $this->fetchNode($queryBuilder);
    }

    public function findRootNodeByType(NodeTypeName $nodeTypeName): ?Node
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('n.nodetypename = :nodeTypeName')->setParameter('nodeTypeName', $nodeTypeName->value)
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->andWhere('n.classification = :nodeAggregateClassification')
                ->setParameter('nodeAggregateClassification', NodeAggregateClassification::CLASSIFICATION_ROOT->value);
        $this->addRestrictionRelationConstraints($queryBuilder, 'n', 'h', ':contentStreamId', ':dimensionSpacePointHash');
        return $this->fetchNode($queryBuilder);
    }

    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('pn.*, ch.name, ph.contentstreamid')
            ->from($this->tableNamePrefix . '_node', 'pn')
            ->innerJoin('pn', $this->tableNamePrefix . '_hierarchyrelation', 'ph', 'ph.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->tableNamePrefix . '_node', 'cn', 'cn.relationanchorpoint = ph.childnodeanchor')
            ->innerJoin('pn', $this->tableNamePrefix . '_hierarchyrelation', 'ch', 'ch.childnodeanchor = pn.relationanchorpoint')
            ->where('cn.nodeaggregateid = :childNodeAggregateId')->setParameter('childNodeAggregateId', $childNodeAggregateId->value)
            ->andWhere('ph.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->value)
            ->andWhere('ch.contentstreamid = :contentStreamId')
            ->andWhere('ph.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->andWhere('ch.dimensionspacepointhash = :dimensionSpacePointHash');
        $this->addRestrictionRelationConstraints($queryBuilder, 'cn', 'ch', ':contentStreamId', ':dimensionSpacePointHash');
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
        $queryBuilder = $this->createQueryBuilder()
            ->select('cn.*, h.name, h.contentstreamid')
            ->from($this->tableNamePrefix . '_node', 'pn')
            ->innerJoin('pn', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->tableNamePrefix . '_node', 'cn', 'cn.relationanchorpoint = h.childnodeanchor')
            ->where('pn.nodeaggregateid = :parentNodeAggregateId')->setParameter('parentNodeAggregateId', $parentNodeAggregateId->value)
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->andWhere('h.name = :edgeName')->setParameter('edgeName', $nodeName->value);
        $this->addRestrictionRelationConstraints($queryBuilder, 'cn', 'h', ':contentStreamId', ':dimensionSpacePointHash');
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
        $ancestors = $this->findAncestorNodes($leafNode->nodeAggregateId, FindAncestorNodesFilter::create())
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

    public function findSubtree(NodeAggregateId $entryNodeAggregateId, FindSubtreeFilter $filter): ?Subtree
    {
        $queryBuilderInitial = $this->createQueryBuilder()
            // @see https://mariadb.com/kb/en/library/recursive-common-table-expressions-overview/#cast-to-avoid-data-truncation
            ->select('n.*, h.name, h.contentstreamid, CAST("ROOT" AS CHAR(50)) AS parentNodeAggregateId, 0 AS level, 0 AS position')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')
            ->andWhere('n.nodeaggregateid = :entryNodeAggregateId');
        $this->addRestrictionRelationConstraints($queryBuilderInitial, 'n', 'h', ':contentStreamId', ':dimensionSpacePointHash');

        $queryBuilderRecursive = $this->createQueryBuilder()
            ->select('c.*, h.name, h.contentstreamid, p.nodeaggregateid AS parentNodeAggregateId, p.level + 1 AS level, h.position')
            ->from('tree', 'p')
            ->innerJoin('p', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.parentnodeanchor = p.relationanchorpoint')
            ->innerJoin('p', $this->tableNamePrefix . '_node', 'c', 'c.relationanchorpoint = h.childnodeanchor')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash');
        if ($filter->maximumLevels !== null) {
            $queryBuilderRecursive->andWhere('p.level < :maximumLevels')->setParameter('maximumLevels', $filter->maximumLevels);
        }
        if ($filter->nodeTypes !== null) {
            $this->addNodeTypeCriteria($queryBuilderRecursive, $filter->nodeTypes, 'c');
        }
        $this->addRestrictionRelationConstraints($queryBuilderRecursive, 'c', 'h', ':contentStreamId', ':dimensionSpacePointHash');

        $queryBuilderCte = $this->createQueryBuilder()
            ->select('*')
            ->from('tree')
            ->orderBy('level')
            ->addOrderBy('position')
            ->setParameter('contentStreamId', $this->contentStreamId->value)
            ->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->setParameter('entryNodeAggregateId', $entryNodeAggregateId->value);

        $result = $this->fetchCteResults($queryBuilderInitial, $queryBuilderRecursive, $queryBuilderCte, 'tree');
        /** @var array<string, Subtree[]> $subtreesByParentNodeId */
        $subtreesByParentNodeId = [];
        foreach (array_reverse($result) as $nodeData) {
            $nodeAggregateId = $nodeData['nodeaggregateid'];
            $parentNodeAggregateId = $nodeData['parentNodeAggregateId'];
            $node = $this->nodeFactory->mapNodeRowToNode($nodeData, $this->dimensionSpacePoint, $this->visibilityConstraints);
            $subtree = new Subtree((int)$nodeData['level'], $node, array_key_exists($nodeAggregateId, $subtreesByParentNodeId) ? array_reverse($subtreesByParentNodeId[$nodeAggregateId]) : []);
            if ($subtree->level === 0) {
                return $subtree;
            }
            if (!array_key_exists($parentNodeAggregateId, $subtreesByParentNodeId)) {
                $subtreesByParentNodeId[$parentNodeAggregateId] = [];
            }
            $subtreesByParentNodeId[$parentNodeAggregateId][] = $subtree;
        }
        return null;
    }

    public function findAncestorNodes(NodeAggregateId $entryNodeAggregateId, FindAncestorNodesFilter $filter): Nodes
    {
        [
            'queryBuilderInitial' => $queryBuilderInitial,
            'queryBuilderRecursive' => $queryBuilderRecursive,
            'queryBuilderCte' => $queryBuilderCte
        ] = $this->buildAncestorNodesQueries($entryNodeAggregateId, $filter);
        $nodeRows = $this->fetchCteResults(
            $queryBuilderInitial,
            $queryBuilderRecursive,
            $queryBuilderCte,
            'ancestry'
        );

        return $this->nodeFactory->mapNodeRowsToNodes(
            $nodeRows,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints
        );
    }

    public function countAncestorNodes(NodeAggregateId $entryNodeAggregateId, CountAncestorNodesFilter $filter): int
    {
        [
            'queryBuilderInitial' => $queryBuilderInitial,
            'queryBuilderRecursive' => $queryBuilderRecursive,
            'queryBuilderCte' => $queryBuilderCte
        ] = $this->buildAncestorNodesQueries($entryNodeAggregateId, $filter);

        return $this->fetchCteCountResult(
            $queryBuilderInitial,
            $queryBuilderRecursive,
            $queryBuilderCte,
            'ancestry'
        );
    }

    public function findClosestNode(NodeAggregateId $entryNodeAggregateId, FindClosestNodeFilter $filter): ?Node
    {
        $queryBuilderInitial = $this->createQueryBuilder()
            ->select('n.*, ph.name, ph.contentstreamid, ph.parentnodeanchor')
            ->from($this->tableNamePrefix . '_node', 'n')
            // we need to join with the hierarchy relation, because we need the node name.
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'ph', 'n.relationanchorpoint = ph.childnodeanchor')
            ->andWhere('ph.contentstreamid = :contentStreamId')
            ->andWhere('ph.dimensionspacepointhash = :dimensionSpacePointHash')
            ->andWhere('n.nodeaggregateid = :entryNodeAggregateId');
        $this->addRestrictionRelationConstraints($queryBuilderInitial, 'n', 'ph', ':contentStreamId', ':dimensionSpacePointHash');

        $queryBuilderRecursive = $this->createQueryBuilder()
            ->select('p.*, h.name, h.contentstreamid, h.parentnodeanchor')
            ->from('ancestry', 'c')
            ->innerJoin('c', $this->tableNamePrefix . '_node', 'p', 'p.relationanchorpoint = c.parentnodeanchor')
            ->innerJoin('p', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = p.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash');
        $this->addRestrictionRelationConstraints($queryBuilderRecursive, 'p', 'h', ':contentStreamId', ':dimensionSpacePointHash');

        $queryBuilderCte = $this->createQueryBuilder()
            ->select('*')
            ->from('ancestry', 'p')
            ->setMaxResults(1)
            ->setParameter('contentStreamId', $this->contentStreamId->value)
            ->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->setParameter('entryNodeAggregateId', $entryNodeAggregateId->value);
        if ($filter->nodeTypes !== null) {
            $this->addNodeTypeCriteria($queryBuilderCte, $filter->nodeTypes, 'p');
        }
        $nodeRows = $this->fetchCteResults(
            $queryBuilderInitial,
            $queryBuilderRecursive,
            $queryBuilderCte,
            'ancestry'
        );
        return $this->nodeFactory->mapNodeRowsToNodes(
            $nodeRows,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints
        )->first();
    }

    public function findDescendantNodes(NodeAggregateId $entryNodeAggregateId, FindDescendantNodesFilter $filter): Nodes
    {
        ['queryBuilderInitial' => $queryBuilderInitial, 'queryBuilderRecursive' => $queryBuilderRecursive, 'queryBuilderCte' => $queryBuilderCte] = $this->buildDescendantNodesQueries($entryNodeAggregateId, $filter);
        if ($filter->ordering !== null) {
            $this->applyOrdering($queryBuilderCte, $filter->ordering);
        }
        if ($filter->pagination !== null) {
            $this->applyPagination($queryBuilderCte, $filter->pagination);
        }
        $queryBuilderCte->addOrderBy('level')->addOrderBy('position');
        $nodeRows = $this->fetchCteResults($queryBuilderInitial, $queryBuilderRecursive, $queryBuilderCte, 'tree');
        return $this->nodeFactory->mapNodeRowsToNodes($nodeRows, $this->dimensionSpacePoint, $this->visibilityConstraints);
    }

    public function countDescendantNodes(NodeAggregateId $entryNodeAggregateId, CountDescendantNodesFilter $filter): int
    {
        ['queryBuilderInitial' => $queryBuilderInitial, 'queryBuilderRecursive' => $queryBuilderRecursive, 'queryBuilderCte' => $queryBuilderCte] = $this->buildDescendantNodesQueries($entryNodeAggregateId, $filter);
        return $this->fetchCteCountResult($queryBuilderInitial, $queryBuilderRecursive, $queryBuilderCte, 'tree');
    }

    public function countNodes(): int
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash);
        try {
            $result = $this->executeQuery($queryBuilder)->fetchOne();
            if (!is_int($result)) {
                throw new \RuntimeException(sprintf('Expected result to be of type integer but got: %s', get_debug_type($result)), 1678366902);
            }
            return $result;
        } catch (DbalDriverException | DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to count all nodes: %s', $e->getMessage()), 1678364741, $e);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'dimensionSpacePoint' => $this->dimensionSpacePoint
        ];
    }

    /** ------------------------------------------- */

    private function findNodeByPathFromStartingNode(NodePath $path, Node $startingNode): ?Node
    {
        $currentNode = $startingNode;

        foreach ($path->getParts() as $edgeName) {
            $currentNode = $this->findChildNodeConnectedThroughEdgeName($currentNode->nodeAggregateId, $edgeName);
            if ($currentNode === null) {
                return null;
            }
        }
        return $currentNode;
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->client->getConnection()->createQueryBuilder();
    }

    private function createUniqueParameterName(): string
    {
        return 'param_' . (++$this->dynamicParameterCount);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $nodeTableAlias
     * @param string $hierarchyRelationTableAlias
     * @param string|null $contentStreamIdParameter if not given, condition will be on the hierachy relation content stream id
     * @param string|null $dimensionspacePointHashParameter if not given, condition will be on the hierachy relation dimension space point hash
     * @return void
     */
    private function addRestrictionRelationConstraints(QueryBuilder $queryBuilder, string $nodeTableAlias = 'n', string $hierarchyRelationTableAlias = 'h', ?string $contentStreamIdParameter = null, ?string $dimensionspacePointHashParameter = null): void
    {
        if ($this->visibilityConstraints->isDisabledContentShown()) {
            return;
        }

        $nodeTablePrefix = $nodeTableAlias === '' ? '' : $nodeTableAlias . '.';
        $hierarchyRelationTablePrefix = $hierarchyRelationTableAlias === '' ? '' : $hierarchyRelationTableAlias . '.';

        $contentStreamIdCondition = 'r.contentstreamid = ' . ($contentStreamIdParameter ?? ($hierarchyRelationTablePrefix . 'contentstreamid'));

        $dimensionspacePointHashCondition = 'r.dimensionspacepointhash = ' . ($dimensionspacePointHashParameter ?? ($hierarchyRelationTablePrefix . 'dimensionspacepointhash'));

        $subQueryBuilder = $this->createQueryBuilder()
            ->select('1')
            ->from($this->tableNamePrefix . '_restrictionrelation', 'r')
            ->where($contentStreamIdCondition)
            ->andWhere($dimensionspacePointHashCondition)
            ->andWhere('r.affectednodeaggregateid = ' . $nodeTablePrefix . 'nodeaggregateid');
        $queryBuilder->andWhere(
            'NOT EXISTS (' . $subQueryBuilder->getSQL() . ')'
        );
    }

    private function addNodeTypeCriteria(QueryBuilder $queryBuilder, NodeTypeCriteria $nodeTypeCriteria, string $nodeTableAlias = 'n'): void
    {
        $nodeTablePrefix = $nodeTableAlias === '' ? '' : $nodeTableAlias . '.';
        $constraintsWithSubNodeTypes = ExpandedNodeTypeCriteria::create($nodeTypeCriteria, $this->nodeTypeManager);
        $allowanceQueryPart = '';
        if (!$constraintsWithSubNodeTypes->explicitlyAllowedNodeTypeNames->isEmpty()) {
            $allowanceQueryPart = $queryBuilder->expr()->in($nodeTablePrefix . 'nodetypename', ':explicitlyAllowedNodeTypeNames');
            $queryBuilder->setParameter('explicitlyAllowedNodeTypeNames', $constraintsWithSubNodeTypes->explicitlyAllowedNodeTypeNames->toStringArray(), Connection::PARAM_STR_ARRAY);
        }
        $denyQueryPart = '';
        if (!$constraintsWithSubNodeTypes->explicitlyDisallowedNodeTypeNames->isEmpty()) {
            $denyQueryPart = $queryBuilder->expr()->notIn($nodeTablePrefix . 'nodetypename', ':explicitlyDisallowedNodeTypeNames');
            $queryBuilder->setParameter('explicitlyDisallowedNodeTypeNames', $constraintsWithSubNodeTypes->explicitlyDisallowedNodeTypeNames->toStringArray(), Connection::PARAM_STR_ARRAY);
        }
        if ($allowanceQueryPart && $denyQueryPart) {
            if ($constraintsWithSubNodeTypes->isWildCardAllowed) {
                $queryBuilder->andWhere($queryBuilder->expr()->or($allowanceQueryPart, $denyQueryPart));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->and($allowanceQueryPart, $denyQueryPart));
            }
        } elseif ($allowanceQueryPart && !$constraintsWithSubNodeTypes->isWildCardAllowed) {
            $queryBuilder->andWhere($allowanceQueryPart);
        } elseif ($denyQueryPart) {
            $queryBuilder->andWhere($denyQueryPart);
        }
    }

    private function addSearchTermConstraints(QueryBuilder $queryBuilder, SearchTerm $searchTerm, string $nodeTableAlias = 'n'): void
    {
        $queryBuilder->andWhere('JSON_SEARCH(' . $nodeTableAlias . '.properties, "one", :searchTermPattern, NULL, "$.*.value") IS NOT NULL')->setParameter('searchTermPattern', '%' . $searchTerm->term . '%');
    }

    private function addPropertyValueConstraints(QueryBuilder $queryBuilder, PropertyValueCriteriaInterface $propertyValue, string $nodeTableAlias = 'n'): void
    {
        $queryBuilder->andWhere($this->propertyValueConstraints($queryBuilder, $propertyValue, $nodeTableAlias));
    }

    private function propertyValueConstraints(QueryBuilder $queryBuilder, PropertyValueCriteriaInterface $propertyValue, string $nodeTableAlias): string
    {
        return match ($propertyValue::class) {
            AndCriteria::class => (string)$queryBuilder->expr()->and($this->propertyValueConstraints($queryBuilder, $propertyValue->criteria1, $nodeTableAlias), $this->propertyValueConstraints($queryBuilder, $propertyValue->criteria2, $nodeTableAlias)),
            NegateCriteria::class => 'NOT (' . $this->propertyValueConstraints($queryBuilder, $propertyValue->criteria, $nodeTableAlias) . ')',
            OrCriteria::class => (string)$queryBuilder->expr()->or($this->propertyValueConstraints($queryBuilder, $propertyValue->criteria1, $nodeTableAlias), $this->propertyValueConstraints($queryBuilder, $propertyValue->criteria2, $nodeTableAlias)),
            PropertyValueContains::class => $this->searchPropertyValueStatement($queryBuilder, $propertyValue->propertyName, '%' . $propertyValue->value . '%', $nodeTableAlias),
            PropertyValueEndsWith::class => $this->searchPropertyValueStatement($queryBuilder, $propertyValue->propertyName, '%' . $propertyValue->value, $nodeTableAlias),
            PropertyValueEquals::class => is_string($propertyValue->value) ? $this->searchPropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, $nodeTableAlias) : $this->comparePropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, '=', $nodeTableAlias),
            PropertyValueGreaterThan::class => $this->comparePropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, '>', $nodeTableAlias),
            PropertyValueGreaterThanOrEqual::class => $this->comparePropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, '>=', $nodeTableAlias),
            PropertyValueLessThan::class => $this->comparePropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, '<', $nodeTableAlias),
            PropertyValueLessThanOrEqual::class => $this->comparePropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, '<=', $nodeTableAlias),
            PropertyValueStartsWith::class => $this->searchPropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value . '%', $nodeTableAlias),
            default => throw new \InvalidArgumentException(sprintf('Invalid/unsupported property value criteria "%s"', get_debug_type($propertyValue)), 1679561062),
        };
    }

    private function comparePropertyValueStatement(QueryBuilder $queryBuilder, PropertyName $propertyName, string|int|float|bool $value, string $operator, string $nodeTableAlias): string
    {
        $paramName = $this->createUniqueParameterName();
        $paramType = match (gettype($value)) {
            'boolean' => ParameterType::BOOLEAN,
            'integer' => ParameterType::INTEGER,
            default => ParameterType::STRING,
        };
        $queryBuilder->setParameter($paramName, $value, $paramType);
        return  $this->extractPropertyValue($propertyName, $nodeTableAlias) . ' ' . $operator . ' :' . $paramName;
    }

    private function extractPropertyValue(PropertyName $propertyName, string $nodeTableAlias): string
    {
        try {
            $escapedPropertyName = addslashes(json_encode($propertyName->value, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to escape property name: %s', $e->getMessage()), 1679394579, $e);
        }
        return 'JSON_EXTRACT(' . $nodeTableAlias . '.properties, \'$.' . $escapedPropertyName . '.value\')';
    }

    private function searchPropertyValueStatement(QueryBuilder $queryBuilder, PropertyName $propertyName, string|bool|int|float $value, string $nodeTableAlias): string
    {
        try {
            $escapedPropertyName = addslashes(json_encode($propertyName->value, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to escape property name: %s', $e->getMessage()), 1679394579, $e);
        }
        if (is_bool($value)) {
            return 'JSON_SEARCH(' . $nodeTableAlias . '.properties, \'one\', \'' . ($value ? 'true' : 'false') . '\', NULL, \'$.' . $escapedPropertyName . '.value\') IS NOT NULL';
        }
        $paramName = $this->createUniqueParameterName();
        $queryBuilder->setParameter($paramName, $value);
        return 'JSON_SEARCH(' . $nodeTableAlias . '.properties, \'one\', :' . $paramName . ', NULL, \'$.' . $escapedPropertyName . '.value\') IS NOT NULL';
    }

    private function buildChildNodesQuery(NodeAggregateId $parentNodeAggregateId, FindChildNodesFilter|CountChildNodesFilter $filter): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid')
            ->from($this->tableNamePrefix . '_node', 'pn')
            ->innerJoin('pn', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->tableNamePrefix . '_node', 'n', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('pn.nodeaggregateid = :parentNodeAggregateId')->setParameter('parentNodeAggregateId', $parentNodeAggregateId->value)
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash);
        if ($filter->nodeTypes !== null) {
            $this->addNodeTypeCriteria($queryBuilder, $filter->nodeTypes);
        }
        if ($filter->searchTerm !== null) {
            $this->addSearchTermConstraints($queryBuilder, $filter->searchTerm);
        }
        if ($filter->propertyValue !== null) {
            $this->addPropertyValueConstraints($queryBuilder, $filter->propertyValue);
        }
        $this->addRestrictionRelationConstraints($queryBuilder, 'n', 'h', ':contentStreamId', ':dimensionSpacePointHash');
        return $queryBuilder;
    }

    private function buildReferencesQuery(bool $backReferences, NodeAggregateId $nodeAggregateId, FindReferencesFilter|FindBackReferencesFilter|CountReferencesFilter|CountBackReferencesFilter $filter): QueryBuilder
    {
        $sourceTablePrefix = $backReferences ? 'd' : 's';
        $destinationTablePrefix = $backReferences ? 's' : 'd';
        $queryBuilder = $this->createQueryBuilder()
            ->select("{$destinationTablePrefix}n.*, {$destinationTablePrefix}h.name, {$destinationTablePrefix}h.contentstreamid, r.name AS referencename, r.properties AS referenceproperties")
            ->from($this->tableNamePrefix . '_hierarchyrelation', 'sh')
            ->innerJoin('sh', $this->tableNamePrefix . '_node', 'sn', 'sn.relationanchorpoint = sh.childnodeanchor')
            ->innerJoin('sh', $this->tableNamePrefix . '_referencerelation', 'r', 'r.nodeanchorpoint = sn.relationanchorpoint')
            ->innerJoin('sh', $this->tableNamePrefix . '_node', 'dn', 'dn.nodeaggregateid = r.destinationnodeaggregateid')
            ->innerJoin('sh', $this->tableNamePrefix . '_hierarchyrelation', 'dh', 'dh.childnodeanchor = dn.relationanchorpoint')
            ->where("{$sourceTablePrefix}n.nodeaggregateid = :nodeAggregateId")->setParameter('nodeAggregateId', $nodeAggregateId->value)
            ->andWhere('dh.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->andWhere('sh.dimensionspacepointhash = :dimensionSpacePointHash')
            ->andWhere('dh.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->value)
            ->andWhere('sh.contentstreamid = :contentStreamId');
        $this->addRestrictionRelationConstraints($queryBuilder, 'dn', 'dh', ':contentStreamId', ':dimensionSpacePointHash');
        $this->addRestrictionRelationConstraints($queryBuilder, 'sn', 'sh', ':contentStreamId', ':dimensionSpacePointHash');
        if ($filter->nodeTypes !== null) {
            $this->addNodeTypeCriteria($queryBuilder, $filter->nodeTypes, "{$destinationTablePrefix}n");
        }
        if ($filter->nodeSearchTerm !== null) {
            $this->addSearchTermConstraints($queryBuilder, $filter->nodeSearchTerm, "{$destinationTablePrefix}n");
        }
        if ($filter->nodePropertyValue !== null) {
            $this->addPropertyValueConstraints($queryBuilder, $filter->nodePropertyValue, "{$destinationTablePrefix}n");
        }
        if ($filter->referenceSearchTerm !== null) {
            $this->addSearchTermConstraints($queryBuilder, $filter->referenceSearchTerm, 'r');
        }
        if ($filter->referencePropertyValue !== null) {
            $this->addPropertyValueConstraints($queryBuilder, $filter->referencePropertyValue, 'r');
        }
        if ($filter->nodePropertyValue !== null) {
            $this->addPropertyValueConstraints($queryBuilder, $filter->nodePropertyValue, "{$destinationTablePrefix}n");
        }
        if ($filter->referenceName !== null) {
            $queryBuilder->andWhere('r.name = :referenceName')->setParameter('referenceName', $filter->referenceName->value);
        }
        if ($filter instanceof FindReferencesFilter || $filter instanceof FindBackReferencesFilter) {
            if ($filter->ordering !== null) {
                $this->applyOrdering($queryBuilder, $filter->ordering, "{$destinationTablePrefix}n");
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
        $parentNodeAnchorSubQuery = $this->createQueryBuilder()
            ->select('sh.parentnodeanchor')
            ->from($this->tableNamePrefix . '_hierarchyrelation', 'sh')
            ->innerJoin('sh', $this->tableNamePrefix . '_node', 'sn', 'sn.relationanchorpoint = sh.childnodeanchor')
            ->where('sn.nodeaggregateid = :siblingNodeAggregateId')
            ->andWhere('sh.contentstreamid = :contentStreamId')
            ->andWhere('sh.dimensionspacepointhash = :dimensionSpacePointHash');

        $siblingPositionSubQuery = $this->createQueryBuilder()
            ->select('sh.position')
            ->from($this->tableNamePrefix . '_hierarchyrelation', 'sh')
            ->innerJoin('sh', $this->tableNamePrefix . '_node', 'sn', 'sn.relationanchorpoint = sh.childnodeanchor')
            ->where('sn.nodeaggregateid = :siblingNodeAggregateId')
            ->andWhere('sh.contentstreamid = :contentStreamId')
            ->andWhere('sh.dimensionspacepointhash = :dimensionSpacePointHash');

        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->andWhere('h.parentnodeanchor = (' . $parentNodeAnchorSubQuery->getSQL() . ')')
            ->andWhere('n.nodeaggregateid != :siblingNodeAggregateId')->setParameter('siblingNodeAggregateId', $siblingNodeAggregateId->value)
            ->andWhere('h.position ' . ($preceding ? '<' : '>') . ' (' . $siblingPositionSubQuery->getSQL() . ')')
            ->orderBy('h.position', $preceding ? 'DESC' : 'ASC');

        $this->addRestrictionRelationConstraints($queryBuilder, 'n', 'h', ':contentStreamId', ':dimensionSpacePointHash');
        if ($filter->nodeTypes !== null) {
            $this->addNodeTypeCriteria($queryBuilder, $filter->nodeTypes);
        }
        if ($filter->searchTerm !== null) {
            $this->addSearchTermConstraints($queryBuilder, $filter->searchTerm);
        }
        if ($filter->propertyValue !== null) {
            $this->addPropertyValueConstraints($queryBuilder, $filter->propertyValue);
        }
        if ($filter->pagination !== null) {
            $this->applyPagination($queryBuilder, $filter->pagination);
        }
        return $queryBuilder;
    }

    /**
     * @return array{queryBuilderInitial: QueryBuilder, queryBuilderRecursive: QueryBuilder, queryBuilderCte: QueryBuilder}
     */
    private function buildAncestorNodesQueries(NodeAggregateId $entryNodeAggregateId, FindAncestorNodesFilter|CountAncestorNodesFilter|FindClosestNodeFilter $filter): array
    {
        $queryBuilderInitial = $this->createQueryBuilder()
            ->select('n.*, ph.name, ph.contentstreamid, ph.parentnodeanchor')
            ->from($this->tableNamePrefix . '_node', 'n')
            // we need to join with the hierarchy relation, because we need the node name.
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'ch', 'ch.parentnodeanchor = n.relationanchorpoint')
            ->innerJoin('ch', $this->tableNamePrefix . '_node', 'c', 'c.relationanchorpoint = ch.childnodeanchor')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'ph', 'n.relationanchorpoint = ph.childnodeanchor')
            ->where('ch.contentstreamid = :contentStreamId')
            ->andWhere('ch.dimensionspacepointhash = :dimensionSpacePointHash')
            ->andWhere('ph.contentstreamid = :contentStreamId')
            ->andWhere('ph.dimensionspacepointhash = :dimensionSpacePointHash')
            ->andWhere('c.nodeaggregateid = :entryNodeAggregateId');
        $this->addRestrictionRelationConstraints($queryBuilderInitial, 'n', 'ph', ':contentStreamId', ':dimensionSpacePointHash');
        $this->addRestrictionRelationConstraints($queryBuilderInitial, 'c', 'ch', ':contentStreamId', ':dimensionSpacePointHash');

        $queryBuilderRecursive = $this->createQueryBuilder()
            ->select('p.*, h.name, h.contentstreamid, h.parentnodeanchor')
            ->from('ancestry', 'c')
            ->innerJoin('c', $this->tableNamePrefix . '_node', 'p', 'p.relationanchorpoint = c.parentnodeanchor')
            ->innerJoin('p', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = p.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash');
        $this->addRestrictionRelationConstraints($queryBuilderRecursive, 'p', 'h', ':contentStreamId', ':dimensionSpacePointHash');

        $queryBuilderCte = $this->createQueryBuilder()
            ->select('*')
            ->from('ancestry', 'p')
            ->setParameter('contentStreamId', $this->contentStreamId->value)
            ->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->setParameter('entryNodeAggregateId', $entryNodeAggregateId->value);
        if ($filter->nodeTypes !== null) {
            $this->addNodeTypeCriteria($queryBuilderCte, $filter->nodeTypes, 'p');
        }
        return compact('queryBuilderInitial', 'queryBuilderRecursive', 'queryBuilderCte');
    }

    /**
     * @return array{queryBuilderInitial: QueryBuilder, queryBuilderRecursive: QueryBuilder, queryBuilderCte: QueryBuilder}
     */
    private function buildDescendantNodesQueries(NodeAggregateId $entryNodeAggregateId, FindDescendantNodesFilter|CountDescendantNodesFilter $filter): array
    {
        $queryBuilderInitial = $this->createQueryBuilder()
            // @see https://mariadb.com/kb/en/library/recursive-common-table-expressions-overview/#cast-to-avoid-data-truncation
            ->select('n.*, h.name, h.contentstreamid, CAST("ROOT" AS CHAR(50)) AS parentNodeAggregateId, 0 AS level, 0 AS position')
            ->from($this->tableNamePrefix . '_node', 'n')
            // we need to join with the hierarchy relation, because we need the node name.
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->innerJoin('n', $this->tableNamePrefix . '_node', 'p', 'p.relationanchorpoint = h.parentnodeanchor')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'ph', 'ph.childnodeanchor = p.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')
            ->andWhere('ph.contentstreamid = :contentStreamId')
            ->andWhere('ph.dimensionspacepointhash = :dimensionSpacePointHash')
            ->andWhere('p.nodeaggregateid = :entryNodeAggregateId');
        $this->addRestrictionRelationConstraints($queryBuilderInitial, 'n', 'h', ':contentStreamId', ':dimensionSpacePointHash');

        $queryBuilderRecursive = $this->createQueryBuilder()
            ->select('c.*, h.name, h.contentstreamid, p.nodeaggregateid AS parentNodeAggregateId, p.level + 1 AS level, h.position')
            ->from('tree', 'p')
            ->innerJoin('p', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.parentnodeanchor = p.relationanchorpoint')
            ->innerJoin('p', $this->tableNamePrefix . '_node', 'c', 'c.relationanchorpoint = h.childnodeanchor')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash');
        $this->addRestrictionRelationConstraints($queryBuilderRecursive, 'c', 'h', ':contentStreamId', ':dimensionSpacePointHash');

        $queryBuilderCte = $this->createQueryBuilder()
            ->select('*')
            ->from('tree', 'n')
            ->setParameter('contentStreamId', $this->contentStreamId->value)
            ->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->setParameter('entryNodeAggregateId', $entryNodeAggregateId->value);
        if ($filter->nodeTypes !== null) {
            $this->addNodeTypeCriteria($queryBuilderCte, $filter->nodeTypes);
        }
        if ($filter->searchTerm !== null) {
            $this->addSearchTermConstraints($queryBuilderCte, $filter->searchTerm);
        }
        if ($filter->propertyValue !== null) {
            $this->addPropertyValueConstraints($queryBuilderCte, $filter->propertyValue);
        }
        return compact('queryBuilderInitial', 'queryBuilderRecursive', 'queryBuilderCte');
    }

    private function applyOrdering(QueryBuilder $queryBuilder, Ordering $ordering, string $nodeTableAlias = 'n'): void
    {
        foreach ($ordering as $orderingField) {
            $order = match ($orderingField->direction) {
                OrderingDirection::ASCENDING => 'ASC',
                OrderingDirection::DESCENDING => 'DESC',
            };
            if ($orderingField->field instanceof PropertyName) {
                $queryBuilder->addOrderBy($this->extractPropertyValue($orderingField->field, $nodeTableAlias), $order);
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
        $queryBuilder
            ->setMaxResults($pagination->limit)
            ->setFirstResult($pagination->offset);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return Result<mixed>
     * @throws DbalException
     */
    private function executeQuery(QueryBuilder $queryBuilder): Result
    {
        $result = $queryBuilder->execute();
        if (!$result instanceof Result) {
            throw new \RuntimeException(sprintf('Expected instance of %s, got %s', Result::class, get_debug_type($result)), 1678370012);
        }
        return $result;
    }

    private function fetchNode(QueryBuilder $queryBuilder): ?Node
    {
        try {
            $nodeRow = $this->executeQuery($queryBuilder)->fetchAssociative();
        } catch (DbalDriverException | DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch node: %s', $e->getMessage()), 1678286030, $e);
        }
        if ($nodeRow === false) {
            return null;
        }
        return $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints
        );
    }

    private function fetchNodes(QueryBuilder $queryBuilder): Nodes
    {
        try {
            $nodeRows = $this->executeQuery($queryBuilder)->fetchAllAssociative();
        } catch (DbalDriverException | DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch nodes: %s', $e->getMessage()), 1678292896, $e);
        }
        return $this->nodeFactory->mapNodeRowsToNodes($nodeRows, $this->dimensionSpacePoint, $this->visibilityConstraints);
    }

    private function fetchCount(QueryBuilder $queryBuilder): int
    {
        try {
            return (int)$this->executeQuery($queryBuilder->select('COUNT(*)')->resetQueryPart('orderBy')->setFirstResult(0)->setMaxResults(1))->fetchOne();
        } catch (DbalDriverException | DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch count: %s', $e->getMessage()), 1679048349, $e);
        }
    }

    private function fetchReferences(QueryBuilder $queryBuilder): References
    {
        try {
            $referenceRows = $this->executeQuery($queryBuilder)->fetchAllAssociative();
        } catch (DbalDriverException | DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch references: %s', $e->getMessage()), 1678364944, $e);
        }
        return $this->nodeFactory->mapReferenceRowsToReferences($referenceRows, $this->dimensionSpacePoint, $this->visibilityConstraints);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchCteResults(QueryBuilder $queryBuilderInitial, QueryBuilder $queryBuilderRecursive, QueryBuilder $queryBuilderCte, string $cteTableName = 'cte'): array
    {
        $query = 'WITH RECURSIVE ' . $cteTableName . ' AS (' . $queryBuilderInitial->getSQL() . ' UNION ' . $queryBuilderRecursive->getSQL() . ') ' . $queryBuilderCte->getSQL();
        $parameters = array_merge($queryBuilderInitial->getParameters(), $queryBuilderRecursive->getParameters(), $queryBuilderCte->getParameters());
        $parameterTypes = array_merge($queryBuilderInitial->getParameterTypes(), $queryBuilderRecursive->getParameterTypes(), $queryBuilderCte->getParameterTypes());
        try {
            return $this->client->getConnection()->fetchAllAssociative($query, $parameters, $parameterTypes);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch CTE result: %s', $e->getMessage()), 1678358108, $e);
        }
    }

    private function fetchCteCountResult(QueryBuilder $queryBuilderInitial, QueryBuilder $queryBuilderRecursive, QueryBuilder $queryBuilderCte, string $cteTableName = 'cte'): int
    {
        $query = 'WITH RECURSIVE ' . $cteTableName . ' AS (' . $queryBuilderInitial->getSQL() . ' UNION ' . $queryBuilderRecursive->getSQL() . ') ' . $queryBuilderCte->select('COUNT(*)')->resetQueryPart('orderBy')->setFirstResult(0)->setMaxResults(1);
        $parameters = array_merge($queryBuilderInitial->getParameters(), $queryBuilderRecursive->getParameters(), $queryBuilderCte->getParameters());
        $parameterTypes = array_merge($queryBuilderInitial->getParameterTypes(), $queryBuilderRecursive->getParameterTypes(), $queryBuilderCte->getParameterTypes());
        try {
            return (int)$this->client->getConnection()->fetchOne($query, $parameters, $parameterTypes);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch CTE count result: %s', $e->getMessage()), 1679047841, $e);
        }
    }
}
