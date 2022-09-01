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
use Doctrine\DBAL\Driver\Exception;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencedNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraintsWithSubNodeTypes;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtrees;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;

/**
 * The content subgraph application repository
 *
 * To be used as a read-only source of nodes.
 *
 *
 *
 * ## Conventions for SQL queries
 *
 * - n -> node
 * - h -> hierarchy edge
 *
 * - if more than one node (parent-child)
 *   - pn -> parent node
 *   - cn -> child node
 *   - h -> the hierarchy edge connecting parent and child
 *   - ph -> the hierarchy edge incoming to the parent (sometimes relevant)
 *
 *
 * @internal the parent {@see ContentSubgraphInterface} is API
 */
final class ContentSubgraph implements ContentSubgraphInterface
{
    public readonly InMemoryCache $inMemoryCache;

    public function __construct(
        private readonly ContentStreamId $contentStreamId,
        private readonly DimensionSpacePoint $dimensionSpacePoint,
        private readonly VisibilityConstraints $visibilityConstraints,
        private readonly DbalClientInterface $client,
        private readonly NodeFactory $nodeFactory,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix
    ) {
        $this->inMemoryCache = new InMemoryCache();
    }

    /**
     * @param SqlQueryBuilder $query
     * @param NodeTypeConstraintsWithSubNodeTypes $nodeTypeConstraints
     * @param string|null $markerToReplaceInQuery
     * @param string $tableReference
     * @param string $concatenation
     */
    protected static function addNodeTypeConstraintsToQuery(
        SqlQueryBuilder $query,
        NodeTypeConstraintsWithSubNodeTypes $nodeTypeConstraints,
        string $markerToReplaceInQuery = null,
        string $tableReference = 'c',
        string $concatenation = 'AND'
    ): void {
        if (!$nodeTypeConstraints->explicitlyAllowedNodeTypeNames->isEmpty()) {
            $allowanceQueryPart = ($tableReference ? $tableReference . '.' : '')
                . 'nodetypename IN (:explicitlyAllowedNodeTypeNames)';
            $query->parameter(
                'explicitlyAllowedNodeTypeNames',
                $nodeTypeConstraints->explicitlyAllowedNodeTypeNames->toStringArray(),
                Connection::PARAM_STR_ARRAY
            );
        } else {
            $allowanceQueryPart = '';
        }
        if (!$nodeTypeConstraints->explicitlyDisallowedNodeTypeNames->isEmpty()) {
            $disAllowanceQueryPart = ($tableReference ? $tableReference . '.' : '')
                . 'nodetypename NOT IN (:explicitlyDisallowedNodeTypeNames)';
            $query->parameter(
                'explicitlyDisallowedNodeTypeNames',
                $nodeTypeConstraints->explicitlyDisallowedNodeTypeNames->toStringArray(),
                Connection::PARAM_STR_ARRAY
            );
        } else {
            $disAllowanceQueryPart = '';
        }

        if ($allowanceQueryPart && $disAllowanceQueryPart) {
            $query->addToQuery(
                ' ' . $concatenation . ' (' . $allowanceQueryPart
                . ($nodeTypeConstraints->isWildCardAllowed ? ' OR ' : ' AND ') . $disAllowanceQueryPart . ')',
                $markerToReplaceInQuery
            );
        } elseif ($allowanceQueryPart && !$nodeTypeConstraints->isWildCardAllowed) {
            $query->addToQuery(
                ' ' . $concatenation . ' ' . $allowanceQueryPart,
                $markerToReplaceInQuery
            );
        } elseif ($disAllowanceQueryPart) {
            $query->addToQuery(
                ' ' . $concatenation . ' ' . $disAllowanceQueryPart,
                $markerToReplaceInQuery
            );
        } else {
            $query->addToQuery('', $markerToReplaceInQuery);
        }
    }

    protected static function addSearchTermConstraintsToQuery(
        SqlQueryBuilder $query,
        ?\Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm $searchTerm,
        string $markerToReplaceInQuery = null,
        string $tableReference = 'c',
        string $concatenation = 'AND'
    ): void {
        if ($searchTerm) {
            // Magic copied from legacy NodeSearchService.

            // Convert to lowercase, then to json, and then trim quotes from json to have valid JSON escaping.
            $likeParameter = '%' . trim(
                json_encode(
                    UnicodeFunctions::strtolower($searchTerm->term),
                    JSON_UNESCAPED_UNICODE + JSON_THROW_ON_ERROR
                ),
                '"'
            ) . '%';

            $query
                ->addToQuery(
                    $concatenation . ' LOWER(' . ($tableReference ? $tableReference . '.' : '')
                    . 'properties) LIKE :term',
                    $markerToReplaceInQuery
                )->parameter('term', $likeParameter);
        } else {
            $query->addToQuery('', $markerToReplaceInQuery);
        };
    }

    /**
     * @param NodeAggregateId $parentNodeAggregateId
     * @param FindChildNodesFilter $filter
     * @return Nodes
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function findChildNodes(
        NodeAggregateId $parentNodeAggregateId,
        FindChildNodesFilter $filter
    ): Nodes {
        if ($filter->limit !== null || $filter->offset !== null) {
            throw new \RuntimeException("TODO: Limit/Offset not yet supported in findChildNodes");
        }
        $nodeTypeConstraintsWithSubNodeTypes = NodeTypeConstraintsWithSubNodeTypes::allowAll();
        if ($filter->nodeTypeConstraints) {
            $nodeTypeConstraintsWithSubNodeTypes = NodeTypeConstraintsWithSubNodeTypes::create(
                $filter->nodeTypeConstraints,
                $this->nodeTypeManager
            );
        }

        $cache = $this->inMemoryCache->getAllChildNodesByNodeIdCache();
        $namedChildNodeCache = $this->inMemoryCache->getNamedChildNodeByNodeIdCache();
        $parentNodeIdCache = $this->inMemoryCache->getParentNodeIdByChildNodeIdCache();

        if (
            $cache->contains(
                $parentNodeAggregateId,
                $nodeTypeConstraintsWithSubNodeTypes
            )
        ) {
            return Nodes::fromArray(
                $cache->findChildNodes(
                    $parentNodeAggregateId,
                    $nodeTypeConstraintsWithSubNodeTypes,
                    $filter->limit,
                    $filter->offset
                )
            );
        }
        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findChildNodes
SELECT c.*, h.name, h.contentstreamid FROM ' . $this->tableNamePrefix . '_node p
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN ' . $this->tableNamePrefix . '_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeaggregateid = :parentNodeAggregateId
 AND h.contentstreamid = :contentStreamId
 AND h.dimensionspacepointhash = :dimensionSpacePointHash'
        )
            ->parameter('parentNodeAggregateId', $parentNodeAggregateId)
            ->parameter(
                'contentStreamId',
                (string)$this->contentStreamId
            )
            ->parameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash);

        self::addNodeTypeConstraintsToQuery($query, $nodeTypeConstraintsWithSubNodeTypes);

        $this->addRestrictionRelationConstraintsToQuery(
            $query,
            'c'
        );
        $query->addToQuery('ORDER BY h.position ASC');

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAllAssociative() as $nodeData) {
            $node = $this->nodeFactory->mapNodeRowToNode(
                $nodeData,
                $this->dimensionSpacePoint,
                $this->visibilityConstraints
            );
            $result[] = $node;
            $namedChildNodeCache->add(
                $parentNodeAggregateId,
                $node->nodeName,
                $node
            );
            $parentNodeIdCache->add(
                $node->nodeAggregateId,
                $parentNodeAggregateId
            );
            $this->inMemoryCache->getNodeByNodeAggregateIdCache()->add(
                $node->nodeAggregateId,
                $node
            );
        }

        //if ($limit === null && $offset === null) { @todo reenable once this is supported
        $cache->add(
            $parentNodeAggregateId,
            $nodeTypeConstraintsWithSubNodeTypes,
            $result
        );
        //}

        return Nodes::fromArray($result);
    }

    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        $cache = $this->inMemoryCache->getNodeByNodeAggregateIdCache();

        if ($cache->knowsAbout($nodeAggregateId)) {
            return $cache->get($nodeAggregateId);
        } else {
            $query = new SqlQueryBuilder();
            $query->addToQuery(
                '
-- ContentSubgraph::findNodeByNodeAggregateId
SELECT n.*, h.name, h.contentstreamid FROM ' . $this->tableNamePrefix . '_node n
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint

 WHERE n.nodeaggregateid = :nodeAggregateId
 AND h.contentstreamid = :contentStreamId
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 '
            )
                ->parameter(
                    'nodeAggregateId',
                    (string)$nodeAggregateId
                )
                ->parameter(
                    'contentStreamId',
                    (string)$this->contentStreamId
                )
                ->parameter(
                    'dimensionSpacePointHash',
                    $this->dimensionSpacePoint->hash
                );

            $query = $this->addRestrictionRelationConstraintsToQuery(
                $query
            );

            $nodeRow = $query->execute($this->getDatabaseConnection())->fetchAssociative();
            if ($nodeRow === false) {
                $cache->rememberNonExistingNodeAggregateId($nodeAggregateId);
                return null;
            }

            $node = $this->nodeFactory->mapNodeRowToNode(
                $nodeRow,
                $this->dimensionSpacePoint,
                $this->visibilityConstraints
            );
            $cache->add($nodeAggregateId, $node);

            return $node;
        }
    }

    private function addRestrictionRelationConstraintsToQuery(
        SqlQueryBuilder $query,
        string $aliasOfNodeInQuery = 'n',
        string $aliasOfHierarchyEdgeInQuery = 'h',
        string $markerToReplaceInQuery = null
    ): SqlQueryBuilder {
        // TODO: make QueryBuilder immutable
        if (!$this->visibilityConstraints->isDisabledContentShown()) {
            $query->addToQuery(
                '
                and not exists (
                    select
                        1
                    from
                        ' . $this->tableNamePrefix . '_restrictionrelation r
                    where
                        r.contentstreamid = ' . $aliasOfHierarchyEdgeInQuery . '.contentstreamid
                        and r.dimensionspacepointhash = ' . $aliasOfHierarchyEdgeInQuery . '.dimensionspacepointhash
                        and r.affectednodeaggregateid = ' . $aliasOfNodeInQuery . '.nodeaggregateid
                )',
                $markerToReplaceInQuery
            );
        } else {
            $query->addToQuery('', $markerToReplaceInQuery);
        }

        return $query;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findReferencedNodes(
        NodeAggregateId $nodeAggregateId,
        FindReferencedNodesFilter $filter
    ): References {
        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findReferencedNodes
SELECT d.*, dh.contentstreamid, dh.name, r.name AS referencename, r.properties AS referenceproperties
 FROM ' . $this->tableNamePrefix . '_hierarchyrelation sh
 INNER JOIN ' . $this->tableNamePrefix . '_node s ON sh.childnodeanchor = s.relationanchorpoint
 INNER JOIN ' . $this->tableNamePrefix . '_referencerelation r ON s.relationanchorpoint = r.nodeanchorpoint
 INNER JOIN ' . $this->tableNamePrefix . '_node d ON r.destinationnodeaggregateid = d.nodeaggregateid
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation dh ON dh.childnodeanchor = d.relationanchorpoint
 WHERE s.nodeaggregateid = :nodeAggregateId
 AND dh.dimensionspacepointhash = :dimensionSpacePointHash
 AND sh.dimensionspacepointhash = :dimensionSpacePointHash
 AND dh.contentstreamid = :contentStreamId
 AND sh.contentstreamid = :contentStreamId
'
        )
            ->parameter('nodeAggregateId', (string)$nodeAggregateId)
            ->parameter(
                'contentStreamId',
                (string)$this->contentStreamId
            )
            ->parameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->parameter('name', (string)$filter->referenceName);

        $this->addRestrictionRelationConstraintsToQuery(
            $query,
            'd',
            'dh'
        );

        if ($filter->referenceName) {
            $query->addToQuery(
                '
 AND r.name = :name
 ORDER BY r.position'
            );
        } else {
            $query->addToQuery(
                '
 ORDER BY r.name, r.position'
            );
        }

        $referenceRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapReferenceRowsToReferences(
            $referenceRows,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints
        );
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findReferencingNodes(
        NodeAggregateId $nodeAggregateId,
        FindReferencingNodesFilter $filter
    ): References {
        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findReferencingNodes
SELECT s.*, sh.contentstreamid, sh.name, r.name AS referencename, r.properties AS referenceproperties
 FROM ' . $this->tableNamePrefix . '_hierarchyrelation sh
 INNER JOIN ' . $this->tableNamePrefix . '_node s ON sh.childnodeanchor = s.relationanchorpoint
 INNER JOIN ' . $this->tableNamePrefix . '_referencerelation r ON s.relationanchorpoint = r.nodeanchorpoint
 INNER JOIN ' . $this->tableNamePrefix . '_node d ON r.destinationnodeaggregateid = d.nodeaggregateid
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation dh ON dh.childnodeanchor = d.relationanchorpoint
 WHERE d.nodeaggregateid = :destinationnodeaggregateid
 AND dh.dimensionspacepointhash = :dimensionSpacePointHash
 AND sh.dimensionspacepointhash = :dimensionSpacePointHash
 AND dh.contentstreamid = :contentStreamId
 AND sh.contentstreamid = :contentStreamId
'
        )
            ->parameter(
                'destinationnodeaggregateid',
                (string)$nodeAggregateId
            )
            ->parameter('contentStreamId', (string)$this->contentStreamId)
            ->parameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->parameter('name', (string)$filter->referenceName);

        if ($filter->referenceName) {
            $query->addToQuery('AND r.name = :name');
        }

        $this->addRestrictionRelationConstraintsToQuery(
            $query,
            's',
            'sh'
        );

        if ($filter->referenceName) {
            $query->addToQuery(
                '
 ORDER BY r.position, s.nodeaggregateid'
            );
        } else {
            $query->addToQuery(
                '
 ORDER BY r.name, r.position, s.nodeaggregateid'
            );
        }

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapReferenceRowsToReferences(
            $nodeRows,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints
        );
    }

    /**
     * @param NodeAggregateId $childNodeAggregateId
     * @return Node|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
    {
        $cache = $this->inMemoryCache->getParentNodeIdByChildNodeIdCache();

        if ($cache->knowsAbout($childNodeAggregateId)) {
            $possibleParentId = $cache->get($childNodeAggregateId);

            if ($possibleParentId === null) {
                return null;
            } else {
                // we here trigger findNodeById,
                // as this might retrieve the Parent Node from the in-memory cache if it has been loaded before
                return $this->findNodeById($possibleParentId);
            }
        }

        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findParentNode
SELECT p.*, h.contentstreamid, hp.name FROM ' . $this->tableNamePrefix . '_node p
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN ' . $this->tableNamePrefix . '_node c ON h.childnodeanchor = c.relationanchorpoint
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation hp ON hp.childnodeanchor = p.relationanchorpoint
 WHERE c.nodeaggregateid = :childNodeAggregateId
 AND h.contentstreamid = :contentStreamId
 AND hp.contentstreamid = :contentStreamId
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 AND hp.dimensionspacepointhash = :dimensionSpacePointHash'
        )
            ->parameter(
                'childNodeAggregateId',
                (string)$childNodeAggregateId
            )
            ->parameter('contentStreamId', (string)$this->contentStreamId)
            ->parameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash);

        $this->addRestrictionRelationConstraintsToQuery(
            $query,
            'p'
        );

        $nodeRow = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        $node = $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints
        ) : null;
        if ($node) {
            $cache->add(
                $childNodeAggregateId,
                $node->nodeAggregateId
            );

            // we also add the parent node to the NodeAggregateId => Node cache;
            // as this might improve cache hit rates as well.
            $this->inMemoryCache->getNodeByNodeAggregateIdCache()->add(
                $node->nodeAggregateId,
                $node
            );
        } else {
            $cache->rememberNonExistingParentNode($childNodeAggregateId);
        }

        return $node;
    }

    /**
     * @param \Neos\ContentRepository\Core\Projection\ContentGraph\NodePath $path
     * @param NodeAggregateId $startingNodeAggregateId
     * @return Node|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findNodeByPath(
        NodePath $path,
        NodeAggregateId $startingNodeAggregateId
    ): ?Node {
        $currentNode = $this->findNodeById($startingNodeAggregateId);
        if (!$currentNode) {
            throw new \RuntimeException(
                'Starting Node (identified by ' . $startingNodeAggregateId . ') does not exist.'
            );
        }
        foreach ($path->getParts() as $edgeName) {
            // id exists here :)
            $currentNode = $this->findChildNodeConnectedThroughEdgeName(
                $currentNode->nodeAggregateId,
                $edgeName
            );
            if (!$currentNode) {
                return null;
            }
        }

        return $currentNode;
    }

    /**
     * @param NodeAggregateId $parentNodeAggregateId
     * @param NodeName $edgeName
     * @return Node|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findChildNodeConnectedThroughEdgeName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $edgeName
    ): ?Node {
        $cache = $this->inMemoryCache->getNamedChildNodeByNodeIdCache();
        if ($cache->contains($parentNodeAggregateId, $edgeName)) {
            return $cache->get($parentNodeAggregateId, $edgeName);
        } else {
            $query = new SqlQueryBuilder();
            $query->addToQuery(
                '
-- ContentGraph::findChildNodeConnectedThroughEdgeName
SELECT
    c.*,
    h.name,
    h.contentstreamid
FROM
    ' . $this->tableNamePrefix . '_node p
INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
    ON h.parentnodeanchor = p.relationanchorpoint
INNER JOIN ' . $this->tableNamePrefix . '_node c
    ON h.childnodeanchor = c.relationanchorpoint
WHERE
    p.nodeaggregateid = :parentNodeAggregateId
    AND h.contentstreamid = :contentStreamId
    AND h.dimensionspacepointhash = :dimensionSpacePointHash
    AND h.name = :edgeName'
            )
                ->parameter(
                    'parentNodeAggregateId',
                    (string)$parentNodeAggregateId
                )
                ->parameter(
                    'contentStreamId',
                    (string)$this->contentStreamId
                )
                ->parameter(
                    'dimensionSpacePointHash',
                    $this->dimensionSpacePoint->hash
                )
                ->parameter('edgeName', (string)$edgeName);

            $this->addRestrictionRelationConstraintsToQuery(
                $query,
                'c'
            );

            $query->addToQuery('ORDER BY h.position LIMIT 1');

            $nodeData = $query->execute($this->getDatabaseConnection())->fetchAssociative();

            if ($nodeData) {
                $node = $this->nodeFactory->mapNodeRowToNode(
                    $nodeData,
                    $this->dimensionSpacePoint,
                    $this->visibilityConstraints
                );
                $cache->add(
                    $parentNodeAggregateId,
                    $edgeName,
                    $node
                );
                $this->inMemoryCache->getNodeByNodeAggregateIdCache()->add(
                    $node->nodeAggregateId,
                    $node
                );
                return $node;
            }
        }

        return null;
    }

    /**
     * @param NodeAggregateId $sibling
     * @param FindPrecedingSiblingsFilter $filter
     * @return Nodes
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function findPrecedingSiblings(
        NodeAggregateId $sibling,
        FindPrecedingSiblingsFilter $filter
    ): Nodes {
        $query = new SqlQueryBuilder();
        $query->addToQuery(
            $this->getSiblingBaseQuery() . '
            AND n.nodeaggregateid != :siblingNodeAggregateId'
        )
            ->parameter('siblingNodeAggregateId', (string)$sibling)
            ->parameter('contentStreamId', (string)$this->contentStreamId)
            ->parameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash);
        $this->addRestrictionRelationConstraintsToQuery($query);

        $query->addToQuery(
            '
    AND h.position < (
        SELECT sibh.position FROM ' . $this->tableNamePrefix . '_hierarchyrelation sibh
            INNER JOIN ' . $this->tableNamePrefix . '_node sib ON sibh.childnodeanchor = sib.relationanchorpoint
        WHERE sib.nodeaggregateid = :siblingNodeAggregateId
            AND sibh.contentstreamid = :contentStreamId
            AND sibh.dimensionspacepointhash = :dimensionSpacePointHash
    )'
        );

        if ($filter->nodeTypeConstraints) {
            $nodeTypeConstraintsWithSubNodeTypes = NodeTypeConstraintsWithSubNodeTypes::create(
                $filter->nodeTypeConstraints,
                $this->nodeTypeManager
            );
            self::addNodeTypeConstraintsToQuery($query, $nodeTypeConstraintsWithSubNodeTypes);
        }
        $query->addToQuery(' ORDER BY h.position DESC');
        if ($filter->limit) {
            $query->addToQuery(' LIMIT ' . $filter->limit);
        }
        if ($filter->offset) {
            $query->addToQuery(' OFFSET ' . $filter->offset);
        }

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAllAssociative() as $nodeRecord) {
            $result[] = $this->nodeFactory->mapNodeRowToNode(
                $nodeRecord,
                $this->dimensionSpacePoint,
                $this->visibilityConstraints
            );
        }

        return Nodes::fromArray($result);
    }

    /**
     * @param NodeAggregateId $sibling
     * @param FindSucceedingSiblingsFilter $filter
     * @return Nodes
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function findSucceedingSiblings(
        NodeAggregateId $sibling,
        FindSucceedingSiblingsFilter $filter
    ): Nodes {
        $query = new SqlQueryBuilder();
        $query->addToQuery(
            $this->getSiblingBaseQuery() . '
            AND n.nodeaggregateid != :siblingNodeAggregateId'
        )
            ->parameter('siblingNodeAggregateId', (string)$sibling)
            ->parameter('contentStreamId', (string)$this->contentStreamId)
            ->parameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash);
        $this->addRestrictionRelationConstraintsToQuery($query);

        $query->addToQuery(
            '
    AND h.position > (
        SELECT sibh.position FROM ' . $this->tableNamePrefix . '_hierarchyrelation sibh
            INNER JOIN ' . $this->tableNamePrefix . '_node sib ON sibh.childnodeanchor = sib.relationanchorpoint
        WHERE sib.nodeaggregateid = :siblingNodeAggregateId
            AND sibh.contentstreamid = :contentStreamId
            AND sibh.dimensionspacepointhash = :dimensionSpacePointHash
    )'
        );

        if ($filter->nodeTypeConstraints) {
            $nodeTypeConstraintsWithSubNodeTypes = NodeTypeConstraintsWithSubNodeTypes::create(
                $filter->nodeTypeConstraints,
                $this->nodeTypeManager
            );
            self::addNodeTypeConstraintsToQuery($query, $nodeTypeConstraintsWithSubNodeTypes);
        }
        $query->addToQuery(' ORDER BY h.position ASC');
        if ($filter->limit) {
            $query->addToQuery(' LIMIT ' . $filter->limit);
        }
        if ($filter->offset) {
            $query->addToQuery(' OFFSET ' . $filter->offset);
        }

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAllAssociative() as $nodeRecord) {
            $result[] = $this->nodeFactory->mapNodeRowToNode(
                $nodeRecord,
                $this->dimensionSpacePoint,
                $this->visibilityConstraints
            );
        }

        return Nodes::fromArray($result);
    }

    protected function getSiblingBaseQuery(): string
    {
        return '
  SELECT n.*, h.contentstreamid, h.name FROM ' . $this->tableNamePrefix . '_node n
  INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
  WHERE h.contentstreamid = :contentStreamId AND h.dimensionspacepointhash = :dimensionSpacePointHash
  AND h.parentnodeanchor = (
      SELECT sibh.parentnodeanchor FROM ' . $this->tableNamePrefix . '_hierarchyrelation sibh
        INNER JOIN ' . $this->tableNamePrefix . '_node sib ON sibh.childnodeanchor = sib.relationanchorpoint
      WHERE sib.nodeaggregateid = :siblingNodeAggregateId
        AND sibh.contentstreamid = :contentStreamId
        AND sibh.dimensionspacepointhash = :dimensionSpacePointHash
  )';
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }


    public function findNodePath(NodeAggregateId $nodeAggregateId): NodePath
    {
        $cache = $this->inMemoryCache->getNodePathCache();

        if ($cache->contains($nodeAggregateId)) {
            /** @var \Neos\ContentRepository\Core\Projection\ContentGraph\NodePath $nodePath */
            $nodePath = $cache->get($nodeAggregateId);
            return $nodePath;
        }

        $result = $this->getDatabaseConnection()->executeQuery(
            '
            -- ContentSubgraph::findNodePath
            with recursive nodePath as (
            SELECT h.name, h.parentnodeanchor FROM ' . $this->tableNamePrefix . '_node n
                 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                    ON h.childnodeanchor = n.relationanchorpoint
                 AND h.contentstreamid = :contentStreamId
                 AND h.dimensionspacepointhash = :dimensionSpacePointHash
                 AND n.nodeaggregateid = :nodeAggregateId

            UNION

                SELECT h.name, h.parentnodeanchor FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                    INNER JOIN nodePath as np ON h.childnodeanchor = np.parentnodeanchor
                    WHERE
                        h.contentstreamid = :contentStreamId
                        AND h.dimensionspacepointhash = :dimensionSpacePointHash

        )
        select * from nodePath',
            [
                'contentStreamId' => (string)$this->contentStreamId,
                'dimensionSpacePointHash' => $this->dimensionSpacePoint->hash,
                'nodeAggregateId' => (string)$nodeAggregateId
            ]
        )->fetchAllAssociative();

        $nodePathSegments = [];

        foreach ($result as $r) {
            $nodePathSegments[] = $r['name'];
        }

        $nodePathSegments = array_reverse($nodePathSegments);
        $nodePath = NodePath::fromPathSegments($nodePathSegments);
        $cache->add($nodeAggregateId, $nodePath);

        return $nodePath;
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Neos\ContentRepository\Core\SharedModel\Exception\NodeConfigurationException
     * @throws NodeTypeNotFoundException
     */
    public function findSubtrees(
        NodeAggregateIds $entryNodeAggregateIds,
        FindSubtreesFilter $filter
    ): Subtrees {
        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findSubtrees

-- we build a set of recursive trees, ready to be rendered e.g. in a menu.
-- Because the menu supports starting at multiple nodes, we also support starting at multiple nodes at once.
with recursive tree as (
     -- --------------------------------
     -- INITIAL query: select the root nodes of the tree; as given in $menuLevelNodeIds
     -- --------------------------------
     select
     	n.*,
     	h.contentstreamid,
     	h.name,

     	-- see
     	-- https://mariadb.com/kb/en/library/recursive-common-table-expressions-overview/#cast-to-avoid-data-truncation
     	CAST("ROOT" AS CHAR(50)) as parentNodeAggregateId,
     	0 as level,
     	0 as position
     from
        ' . $this->tableNamePrefix . '_node n
     -- we need to join with the hierarchy relation, because we need the node name.
     inner join ' . $this->tableNamePrefix . '_hierarchyrelation h
        on h.childnodeanchor = n.relationanchorpoint
     where
        n.nodeaggregateid in (:entryNodeAggregateIds)
        and h.contentstreamid = :contentStreamId
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
		###VISIBILITY_CONSTRAINTS_INITIAL###
union
     -- --------------------------------
     -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
     -- --------------------------------
     select
        c.*,
        h.contentstreamid,
        h.name,

     	p.nodeaggregateid as parentNodeAggregateId,
     	p.level + 1 as level,
     	h.position
     from
        tree p
	 inner join ' . $this->tableNamePrefix . '_hierarchyrelation h
        on h.parentnodeanchor = p.relationanchorpoint
	 inner join ' . $this->tableNamePrefix . '_node c
	    on h.childnodeanchor = c.relationanchorpoint
	 where
	 	h.contentstreamid = :contentStreamId
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
		and p.level + 1 <= :maximumLevels
        ###NODE_TYPE_CONSTRAINTS###
        ###VISIBILITY_CONSTRAINTS_RECURSION###

   -- select relationanchorpoint from ' . $this->tableNamePrefix . '_node
)
select * from tree
order by level asc, position asc;'
        )
            ->parameter(
                'entryNodeAggregateIds',
                $entryNodeAggregateIds->toStringArray(),
                Connection::PARAM_STR_ARRAY
            )
            ->parameter('contentStreamId', (string)$this->contentStreamId)
            ->parameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash)
            ->parameter('maximumLevels', $filter->maximumLevels);

        $nodeTypeConstraintsWithSubNodeTypes = NodeTypeConstraintsWithSubNodeTypes::create(
            $filter->nodeTypeConstraints,
            $this->nodeTypeManager
        );

        self::addNodeTypeConstraintsToQuery(
            $query,
            $nodeTypeConstraintsWithSubNodeTypes,
            '###NODE_TYPE_CONSTRAINTS###'
        );

        $this->addRestrictionRelationConstraintsToQuery(
            $query,
            'n',
            'h',
            '###VISIBILITY_CONSTRAINTS_INITIAL###'
        );
        $this->addRestrictionRelationConstraintsToQuery(
            $query,
            'c',
            'h',
            '###VISIBILITY_CONSTRAINTS_RECURSION###'
        );

        $result = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        $subtreesByNodeId = [];
        $rootSubtrees = $subtreesByNodeId['ROOT'] = Subtrees::createEmpty();

        foreach ($result as $nodeData) {
            $node = $this->nodeFactory->mapNodeRowToNode(
                $nodeData,
                $this->dimensionSpacePoint,
                $this->visibilityConstraints
            );
            $this->inMemoryCache->getNodeByNodeAggregateIdCache()->add(
                $node->nodeAggregateId,
                $node
            );

            if (!isset($subtreesByNodeId[$nodeData['parentNodeAggregateId']])) {
                throw new \Exception('TODO: must not happen');
            }

            $subtree = new Subtree((int)$nodeData['level'], $node);
            $subtreesByNodeId[$nodeData['parentNodeAggregateId']]->add($subtree);
            $subtreesByNodeId[$nodeData['nodeaggregateid']] = $subtree;

            // also add the parents to the child -> parent cache.
            $parentSubtree = $subtreesByNodeId[$nodeData['parentNodeAggregateId']];
            if ($parentSubtree instanceof Subtree) {
                $this->inMemoryCache->getParentNodeIdByChildNodeIdCache()->add(
                    $node->nodeAggregateId,
                    $parentSubtree->node->nodeAggregateId
                );
            }
        }

        return $rootSubtrees;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws NodeTypeNotFoundException
     */
    public function findDescendants(
        NodeAggregateIds $entryNodeAggregateIds,
        FindDescendantsFilter $filter
    ): Nodes {
        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findDescendants

-- we find all nodes matching the given constraints that are descendants of one of the given aggregates
with recursive tree as (
     -- --------------------------------
     -- INITIAL query: select the entry nodes
     -- --------------------------------
     select
     	n.*,
     	h.contentstreamid,
     	h.name,

     	-- see
     	-- https://mariadb.com/kb/en/library/recursive-common-table-expressions-overview/#cast-to-avoid-data-truncation
     	CAST("ROOT" AS CHAR(50)) as parentNodeAggregateId,
     	0 as level,
     	0 as position
     from
        ' . $this->tableNamePrefix . '_node n
     -- we need to join with the hierarchy relation, because we need the node name.
     INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
        ON h.childnodeanchor = n.relationanchorpoint
     INNER JOIN ' . $this->tableNamePrefix . '_node p
        ON p.relationanchorpoint = h.parentnodeanchor
     INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ph
        on ph.childnodeanchor = p.relationanchorpoint
     WHERE
        p.nodeaggregateid in (:entryNodeAggregateIds)
        AND h.contentstreamid = :contentStreamId
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
		AND ph.contentstreamid = :contentStreamId
		AND ph.dimensionspacepointhash = :dimensionSpacePointHash
		###VISIBILITY_CONSTRAINTS_INITIAL###
union
     -- --------------------------------
     -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
     -- --------------------------------
     select
        c.*,
        h.contentstreamid,
        h.name,

     	p.nodeaggregateid as parentNodeAggregateId,
     	p.level + 1 as level,
     	h.position
     from
        tree p
	 inner join ' . $this->tableNamePrefix . '_hierarchyrelation h
        on h.parentnodeanchor = p.relationanchorpoint
	 inner join ' . $this->tableNamePrefix . '_node c
	    on h.childnodeanchor = c.relationanchorpoint
	 where
	 	h.contentstreamid = :contentStreamId
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
        ###VISIBILITY_CONSTRAINTS_RECURSION###

   -- select relationanchorpoint from ' . $this->tableNamePrefix . '_node
)
select * from tree
where
    1=1
    ###NODE_TYPE_CONSTRAINTS###
    ###SEARCH_TERM_CONSTRAINTS###
order by level asc, position asc;'
        )
            ->parameter(
                'entryNodeAggregateIds',
                $entryNodeAggregateIds->toStringArray(),
                Connection::PARAM_STR_ARRAY
            )
            ->parameter('contentStreamId', (string)$this->contentStreamId)
            ->parameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash);

        $nodeTypeConstraintsWithSubNodeTypes = NodeTypeConstraintsWithSubNodeTypes::create(
            $filter->nodeTypeConstraints,
            $this->nodeTypeManager
        );
        self::addNodeTypeConstraintsToQuery(
            $query,
            $nodeTypeConstraintsWithSubNodeTypes,
            '###NODE_TYPE_CONSTRAINTS###',
            ''
        );
        self::addSearchTermConstraintsToQuery(
            $query,
            $filter->searchTerm,
            '###SEARCH_TERM_CONSTRAINTS###',
            ''
        );
        $this->addRestrictionRelationConstraintsToQuery(
            $query,
            'n',
            'h',
            '###VISIBILITY_CONSTRAINTS_INITIAL###'
        );
        $this->addRestrictionRelationConstraintsToQuery(
            $query,
            'c',
            'h',
            '###VISIBILITY_CONSTRAINTS_RECURSION###'
        );

        // TODO: maybe make Nodes lazy-capable as well (so we can yield the results inside the foreach loop)
        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAllAssociative() as $nodeRecord) {
            $result[] = $this->nodeFactory->mapNodeRowToNode(
                $nodeRecord,
                $this->dimensionSpacePoint,
                $this->visibilityConstraints
            );
        }

        return Nodes::fromArray($result);
    }

    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function countNodes(): int
    {
        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
SELECT COUNT(*) FROM ' . $this->tableNamePrefix . '_node n
 JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE h.contentstreamid = :contentStreamId
 AND h.dimensionspacepointhash = :dimensionSpacePointHash'
        )
            ->parameter('contentStreamId', (string)$this->contentStreamId)
            ->parameter('dimensionSpacePointHash', $this->dimensionSpacePoint->hash);

        $row = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        return $row ? (int)$row['COUNT(*)'] : 0;
    }
}
