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
use Doctrine\DBAL\Query\QueryBuilder;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencedNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraintsWithSubNodeTypes;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtrees;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
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
    public function __construct(
        private readonly ContentStreamId $contentStreamId,
        private readonly DimensionSpacePoint $dimensionSpacePoint,
        private readonly VisibilityConstraints $visibilityConstraints,
        private readonly DbalClientInterface $client,
        private readonly NodeFactory $nodeFactory,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix
    ) {
    }

    public function findChildNodes(NodeAggregateId $parentNodeAggregateId, FindChildNodesFilter $filter): Nodes
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid')
            ->from($this->tableNamePrefix . '_node', 'pn')
            ->innerJoin('pn', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->tableNamePrefix . '_node', 'n', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('pn.nodeaggregateid = :parentNodeAggregateId')->setParameter('parentNodeAggregateId', $parentNodeAggregateId->getValue())
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->getValue())
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->orderBy('h.position', 'ASC');
        if ($filter->nodeTypeConstraints !== null) {
            $this->addNodeTypeConstraints($queryBuilder, $filter->nodeTypeConstraints);
        }
        $this->addRestrictionRelationConstraints($queryBuilder);

        if ($filter->limit !== null) {
            $queryBuilder->setMaxResults($filter->limit);
        }
        if ($filter->offset !== null) {
            $queryBuilder->setFirstResult($filter->offset);
        }
        return $this->fetchNodes($queryBuilder);
    }

    public function findReferencedNodes(NodeAggregateId $nodeAggregateId, FindReferencedNodesFilter $filter): References
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('dn.*, dh.contentstreamid, dh.name, r.name AS referencename, r.properties AS referenceproperties')
            ->from($this->tableNamePrefix . '_hierarchyrelation', 'sh')
            ->innerJoin('sh', $this->tableNamePrefix . '_node', 'sn', 'sn.relationanchorpoint = sh.childnodeanchor')
            ->innerJoin('sh', $this->tableNamePrefix . '_referencerelation', 'r', 'r.nodeanchorpoint = sn.relationanchorpoint')
            ->innerJoin('sh', $this->tableNamePrefix . '_node', 'dn', 'dn.nodeaggregateid = r.destinationnodeaggregateid')
            ->innerJoin('sh', $this->tableNamePrefix . '_hierarchyrelation', 'dh', 'dh.childnodeanchor = dn.relationanchorpoint')
            ->where('sn.nodeaggregateid = :nodeAggregateId')->setParameter('nodeAggregateId', $nodeAggregateId->getValue())
            ->andWhere('dh.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->andWhere('sh.dimensionspacepointhash = :dimensionSpacePointHash')
            ->andWhere('dh.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->getValue())
            ->andWhere('sh.contentstreamid = :contentStreamId');
        if ($filter->referenceName === null) {
            $queryBuilder->addOrderBy('r.name');
        }
        $queryBuilder->addOrderBy('r.position');
        $this->addRestrictionRelationConstraints($queryBuilder, 'dn', 'dh');
        $this->addRestrictionRelationConstraints($queryBuilder, 'sn', 'sh');
        if ($filter->referenceName !== null) {
            $queryBuilder->andWhere('r.name = :referenceName')->setParameter('referenceName', $filter->referenceName->value);
        }
        return $this->fetchReferences($queryBuilder);
    }

    public function findReferencingNodes(NodeAggregateId $nodeAggregateId, FindReferencingNodesFilter $filter): References
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('sn.*, sh.contentstreamid, sh.name, r.name AS referencename, r.properties AS referenceproperties')
            ->from($this->tableNamePrefix . '_hierarchyrelation', 'sh')
            ->innerJoin('sh', $this->tableNamePrefix . '_node', 'sn', 'sn.relationanchorpoint = sh.childnodeanchor')
            ->innerJoin('sh', $this->tableNamePrefix . '_referencerelation', 'r', 'r.nodeanchorpoint = sn.relationanchorpoint')
            ->innerJoin('sh', $this->tableNamePrefix . '_node', 'dn', 'dn.nodeaggregateid = r.destinationnodeaggregateid')
            ->innerJoin('sh', $this->tableNamePrefix . '_hierarchyrelation', 'dh', 'dh.childnodeanchor = dn.relationanchorpoint')
            ->where('dn.nodeaggregateid = :nodeAggregateId')->setParameter('nodeAggregateId', $nodeAggregateId->getValue())
            ->andWhere('dh.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->andWhere('sh.dimensionspacepointhash = :dimensionSpacePointHash')
            ->andWhere('dh.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->getValue())
            ->andWhere('sh.contentstreamid = :contentStreamId');
        if ($filter->referenceName === null) {
            $queryBuilder->addOrderBy('r.name');
        }
        $queryBuilder->addOrderBy('r.position');
        $queryBuilder->addOrderBy('sn.nodeaggregateid');
        $this->addRestrictionRelationConstraints($queryBuilder, 'dn', 'dh');
        $this->addRestrictionRelationConstraints($queryBuilder, 'sn', 'sh');
        if ($filter->referenceName !== null) {
            $queryBuilder->andWhere('r.name = :referenceName')->setParameter('referenceName', $filter->referenceName->value);
        }
        return $this->fetchReferences($queryBuilder);
    }

    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('n.nodeaggregateid = :nodeAggregateId')->setParameter('nodeAggregateId', $nodeAggregateId->getValue())
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->getValue())
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash);
        $this->addRestrictionRelationConstraints($queryBuilder);
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
            ->where('cn.nodeaggregateid = :childNodeAggregateId')->setParameter('childNodeAggregateId', $childNodeAggregateId->getValue())
            ->andWhere('ph.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->getValue())
            ->andWhere('ch.contentstreamid = :contentStreamId')
            ->andWhere('ph.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->andWhere('ch.dimensionspacepointhash = :dimensionSpacePointHash');
        $this->addRestrictionRelationConstraints($queryBuilder, 'cn', 'ch');
        return $this->fetchNode($queryBuilder);
    }

    public function findNodeByPath(NodePath $path, NodeAggregateId $startingNodeAggregateId): ?Node
    {
        $currentNode = $this->findNodeById($startingNodeAggregateId);
        if ($currentNode === null) {
            return null;
        }
        foreach ($path->getParts() as $edgeName) {
            // id exists here :)
            $currentNode = $this->findChildNodeConnectedThroughEdgeName($currentNode->nodeAggregateId, $edgeName);
            if ($currentNode === null) {
                return null;
            }
        }
        return $currentNode;
    }

    public function findChildNodeConnectedThroughEdgeName(NodeAggregateId $parentNodeAggregateId, NodeName $edgeName): ?Node
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('cn.*, h.name, h.contentstreamid')
            ->from($this->tableNamePrefix . '_node', 'pn')
            ->innerJoin('pn', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->tableNamePrefix . '_node', 'cn', 'cn.relationanchorpoint = h.childnodeanchor')
            ->where('pn.nodeaggregateid = :parentNodeAggregateId')->setParameter('parentNodeAggregateId', $parentNodeAggregateId->getValue())
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->getValue())
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->andWhere('h.name = :edgeName')->setParameter('edgeName', (string)$edgeName);
        $this->addRestrictionRelationConstraints($queryBuilder, 'cn');
        return $this->fetchNode($queryBuilder);
    }

    public function findSucceedingSiblings(NodeAggregateId $sibling, FindSucceedingSiblingsFilter $filter): Nodes
    {
        $queryBuilder = $this->buildSiblingsQuery(false, $sibling, $filter->nodeTypeConstraints, $filter->limit, $filter->offset);
        return $this->fetchNodes($queryBuilder);
    }

    public function findPrecedingSiblings(NodeAggregateId $sibling, FindPrecedingSiblingsFilter $filter): Nodes
    {
        $queryBuilder = $this->buildSiblingsQuery(true, $sibling, $filter->nodeTypeConstraints, $filter->limit, $filter->offset);
        return $this->fetchNodes($queryBuilder);
    }

    public function findNodePath(NodeAggregateId $nodeAggregateId): NodePath
    {
        $queryBuilderInitial = $this->createQueryBuilder()
            ->select('h.name, h.parentnodeanchor')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')
            ->andWhere('n.nodeaggregateid = :nodeAggregateId');
        $this->addRestrictionRelationConstraints($queryBuilderInitial);

        $queryBuilderRecursive = $this->createQueryBuilder()
            ->select('h.name, h.parentnodeanchor')
            ->from($this->tableNamePrefix . '_hierarchyrelation', 'h')
            ->innerJoin('h', 'nodePath', 'np', 'np.parentnodeanchor = h.childnodeanchor')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash');

        $queryBuilderCte = $this->createQueryBuilder()
            ->select('*')
            ->from('nodePath')
            ->setParameter('contentStreamId', $this->contentStreamId->getValue())
            ->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->setParameter('nodeAggregateId', $nodeAggregateId->getValue());

        $result = $this->fetchCteResults($queryBuilderInitial, $queryBuilderRecursive, $queryBuilderCte, 'nodePath');
        if ($result === []) {
            throw new \InvalidArgumentException(sprintf('Failed to retrieve node path for node "%s"', $nodeAggregateId->getValue()), 1678391715);
        }
        return NodePath::fromPathSegments(array_reverse(array_column($result, 'name')));
    }

    public function findSubtrees(NodeAggregateIds $entryNodeAggregateIds, FindSubtreesFilter $filter): Subtrees
    {
        $queryBuilderInitial = $this->createQueryBuilder()
            // @see https://mariadb.com/kb/en/library/recursive-common-table-expressions-overview/#cast-to-avoid-data-truncation
            ->select('n.*, h.name, h.contentstreamid, CAST("ROOT" AS CHAR(50)) AS parentNodeAggregateId, 0 AS level, 0 AS position')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')
            ->andWhere('n.nodeaggregateid IN (:entryNodeAggregateIds)');
        $this->addRestrictionRelationConstraints($queryBuilderInitial);

        $queryBuilderRecursive = $this->createQueryBuilder()
            ->select('c.*, h.contentstreamid, h.name, p.nodeaggregateid AS parentNodeAggregateId, p.level + 1 AS level, h.position')
            ->from('tree', 'p')
            ->innerJoin('p', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.parentnodeanchor = p.relationanchorpoint')
            ->innerJoin('p', $this->tableNamePrefix . '_node', 'c', 'c.relationanchorpoint = h.childnodeanchor')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash');
        if ($filter->maximumLevels !== null) {
            $queryBuilderRecursive->andWhere('p.level < :maximumLevels')->setParameter('maximumLevels', $filter->maximumLevels);
        }
        if ($filter->nodeTypeConstraints !== null) {
            $this->addNodeTypeConstraints($queryBuilderRecursive, $filter->nodeTypeConstraints, 'c');
        }
        $this->addRestrictionRelationConstraints($queryBuilderRecursive, 'c');

        $queryBuilderCte = $this->createQueryBuilder()
            ->select('*')
            ->from('tree')
            ->orderBy('level')
            ->addOrderBy('position')
            ->setParameter('contentStreamId', $this->contentStreamId->getValue())
            ->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->setParameter('entryNodeAggregateIds', $entryNodeAggregateIds->toStringArray(), Connection::PARAM_STR_ARRAY);

        $result = $this->fetchCteResults($queryBuilderInitial, $queryBuilderRecursive, $queryBuilderCte, 'tree');
        $subtreesByNodeId = [];
        $rootSubtrees = $subtreesByNodeId['ROOT'] = Subtrees::createEmpty();

        foreach ($result as $nodeData) {
            $node = $this->nodeFactory->mapNodeRowToNode(
                $nodeData,
                $this->dimensionSpacePoint,
                $this->visibilityConstraints
            );
            $subtree = new Subtree((int)$nodeData['level'], $node);
            $subtreesByNodeId[$nodeData['parentNodeAggregateId']]->add($subtree);
            $subtreesByNodeId[$nodeData['nodeaggregateid']] = $subtree;
        }
        return $rootSubtrees;
    }

    public function findDescendants(NodeAggregateIds $entryNodeAggregateIds, FindDescendantsFilter $filter): Nodes
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
            ->andWhere('p.nodeaggregateid IN (:entryNodeAggregateIds)');
        $this->addRestrictionRelationConstraints($queryBuilderInitial);

        $queryBuilderRecursive = $this->createQueryBuilder()
            ->select('c.*, h.contentstreamid, h.name, p.nodeaggregateid AS parentNodeAggregateId, p.level + 1 AS level, h.position')
            ->from('tree', 'p')
            ->innerJoin('p', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.parentnodeanchor = p.relationanchorpoint')
            ->innerJoin('p', $this->tableNamePrefix . '_node', 'c', 'c.relationanchorpoint = h.childnodeanchor')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash');
        $this->addRestrictionRelationConstraints($queryBuilderRecursive, 'c');

        $queryBuilderCte = $this->createQueryBuilder()
            ->select('*')
            ->from('tree')
            ->orderBy('level')
            ->addOrderBy('position')
            ->setParameter('contentStreamId', $this->contentStreamId->getValue())
            ->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->setParameter('entryNodeAggregateIds', $entryNodeAggregateIds->toStringArray(), Connection::PARAM_STR_ARRAY);
        if ($filter->nodeTypeConstraints !== null) {
            $this->addNodeTypeConstraints($queryBuilderCte, $filter->nodeTypeConstraints, '');
        }
        if ($filter->searchTerm !== null) {
            $queryBuilderCte->andWhere('JSON_SEARCH(properties, "one", :searchTermPrefix, NULL, "$.*.value") IS NOT NULL')->setParameter('searchTermPrefix', $filter->searchTerm->term . '%');
        }
        $nodeRows = $this->fetchCteResults($queryBuilderInitial, $queryBuilderRecursive, $queryBuilderCte, 'tree');
        return $this->nodeFactory->mapNodeRowsToNodes($nodeRows, $this->dimensionSpacePoint, $this->visibilityConstraints);
    }

    public function countNodes(): int
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($this->tableNamePrefix . '_node', 'n')
            ->innerJoin('n', $this->tableNamePrefix . '_hierarchyrelation', 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->getValue())
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

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->client->getConnection()->createQueryBuilder();
    }

    private function addRestrictionRelationConstraints(QueryBuilder $queryBuilder, string $nodeTableAlias = 'n', string $hierarchyRelationTableAlias = 'h'): void
    {
        if ($this->visibilityConstraints->isDisabledContentShown()) {
            return;
        }
        $nodeTablePrefix = $nodeTableAlias === '' ? '' : $nodeTableAlias . '.';
        $hierarchyRelationTablePrefix = $hierarchyRelationTableAlias === '' ? '' : $hierarchyRelationTableAlias . '.';
        $subQueryBuilder = $this->createQueryBuilder()
            ->select('1')
            ->from($this->tableNamePrefix . '_restrictionrelation', 'r')
            ->where('r.contentstreamid = ' . $hierarchyRelationTablePrefix . 'contentstreamid')
            ->andWhere('r.dimensionspacepointhash = ' . $hierarchyRelationTablePrefix . 'dimensionspacepointhash')
            ->andWhere('r.affectednodeaggregateid = ' . $nodeTablePrefix . 'nodeaggregateid');
        $queryBuilder->andWhere(
            'NOT EXISTS (' . $subQueryBuilder->getSQL() . ')'
        );
    }

    private function addNodeTypeConstraints(QueryBuilder $queryBuilder, NodeTypeConstraints $nodeTypeConstraints, string $nodeTableAlias = 'n'): void
    {
        $nodeTablePrefix = $nodeTableAlias === '' ? '' : $nodeTableAlias . '.';
        $constraintsWithSubNodeTypes = NodeTypeConstraintsWithSubNodeTypes::create($nodeTypeConstraints, $this->nodeTypeManager);
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

    private function buildSiblingsQuery(bool $preceding, NodeAggregateId $siblingNodeAggregateId, ?NodeTypeConstraints $nodeTypeConstraints, ?int $limit, ?int $offset): QueryBuilder
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
            ->where('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->contentStreamId->getValue())
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->andWhere('h.parentnodeanchor = (' . $parentNodeAnchorSubQuery->getSQL() . ')')
            ->andWhere('n.nodeaggregateid != :siblingNodeAggregateId')->setParameter('siblingNodeAggregateId', $siblingNodeAggregateId->getValue())
            ->andWhere('h.position ' . ($preceding ? '<' : '>') . ' (' . $siblingPositionSubQuery->getSQL() . ')')
            ->orderBy('h.position', $preceding ? 'DESC' : 'ASC');

        $this->addRestrictionRelationConstraints($queryBuilder);
        if ($nodeTypeConstraints !== null) {
            $this->addNodeTypeConstraints($queryBuilder, $nodeTypeConstraints);
        }
        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }
        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }
        return $queryBuilder;
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
}
