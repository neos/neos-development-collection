<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Doctrine\DBAL\Connection;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Projection\Content\InMemoryCache;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\Content\Nodes;
use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\EventSourcedContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraints;
use Neos\Flow\Annotations as Flow;
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
 * @api
 */
final class ContentSubgraph implements ContentSubgraphInterface
{
    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var InMemoryCache
     */
    protected $inMemoryCache;

    /**
     * @var ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    protected $dimensionSpacePoint;

    /**
     * @var \Neos\ContentRepository\SharedModel\VisibilityConstraints
     */
    protected $visibilityConstraints;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->visibilityConstraints = $visibilityConstraints;
        $this->inMemoryCache = new InMemoryCache();
    }

    /**
     * @param SqlQueryBuilder $query
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param string|null $markerToReplaceInQuery
     * @param string $tableReference
     * @param string $concatenation
     */
    protected static function addNodeTypeConstraintsToQuery(
        SqlQueryBuilder $query,
        NodeTypeConstraints $nodeTypeConstraints = null,
        string $markerToReplaceInQuery = null,
        string $tableReference = 'c',
        string $concatenation = 'AND'
    ): void {
        if ($nodeTypeConstraints) {
            if (!empty($nodeTypeConstraints->getExplicitlyAllowedNodeTypeNames())) {
                $allowanceQueryPart = ($tableReference ? $tableReference . '.' : '')
                    . 'nodetypename IN (:explicitlyAllowedNodeTypeNames)';
                $query->parameter(
                    'explicitlyAllowedNodeTypeNames',
                    $nodeTypeConstraints->getExplicitlyAllowedNodeTypeNames(),
                    Connection::PARAM_STR_ARRAY
                );
            } else {
                $allowanceQueryPart = '';
            }
            if (!empty($nodeTypeConstraints->getExplicitlyDisallowedNodeTypeNames())) {
                $disAllowanceQueryPart = ($tableReference ? $tableReference . '.' : '')
                    . 'nodetypename NOT IN (:explicitlyDisallowedNodeTypeNames)';
                $query->parameter(
                    'explicitlyDisallowedNodeTypeNames',
                    $nodeTypeConstraints->getExplicitlyDisallowedNodeTypeNames(),
                    Connection::PARAM_STR_ARRAY
                );
            } else {
                $disAllowanceQueryPart = '';
            }

            if ($allowanceQueryPart && $disAllowanceQueryPart) {
                $query->addToQuery(
                    ' ' . $concatenation .' (' . $allowanceQueryPart
                        . ($nodeTypeConstraints->isWildcardAllowed() ? ' OR ' : ' AND ') . $disAllowanceQueryPart . ')',
                    $markerToReplaceInQuery
                );
            } elseif ($allowanceQueryPart && !$nodeTypeConstraints->isWildcardAllowed()) {
                $query->addToQuery(
                    ' ' . $concatenation .' ' . $allowanceQueryPart,
                    $markerToReplaceInQuery
                );
            } elseif ($disAllowanceQueryPart) {
                $query->addToQuery(
                    ' ' . $concatenation .' ' . $disAllowanceQueryPart,
                    $markerToReplaceInQuery
                );
            } else {
                $query->addToQuery('', $markerToReplaceInQuery);
            }
        }
    }

    protected static function addSearchTermConstraintsToQuery(
        SqlQueryBuilder $query,
        ?\Neos\ContentRepository\Projection\Content\SearchTerm $searchTerm,
        string $markerToReplaceInQuery = null,
        string $tableReference = 'c',
        string $concatenation = 'AND'
    ): void {
        if ($searchTerm) {
            // Magic copied from legacy NodeSearchService.

            // Convert to lowercase, then to json, and then trim quotes from json to have valid JSON escaping.
            $likeParameter = '%' . trim(json_encode(
                UnicodeFunctions::strtolower($searchTerm->getTerm()),
                JSON_UNESCAPED_UNICODE + JSON_THROW_ON_ERROR
            ), '"') . '%';

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

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     * @throws \Exception
     */
    public function findChildNodes(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes {
        if ($limit !== null || $offset !== null) {
            throw new \RuntimeException("TODO: Limit/Offset not yet supported in findChildNodes");
        }

        $cache = $this->inMemoryCache->getAllChildNodesByNodeIdentifierCache();
        $namedChildNodeCache = $this->inMemoryCache->getNamedChildNodeByNodeIdentifierCache();
        $parentNodeIdentifierCache = $this->inMemoryCache->getParentNodeIdentifierByChildNodeIdentifierCache();

        if ($cache->contains(
            $parentNodeAggregateIdentifier,
            $nodeTypeConstraints
        )) {
            return Nodes::fromArray($cache->findChildNodes(
                $parentNodeAggregateIdentifier,
                $nodeTypeConstraints,
                $limit,
                $offset
            ));
        }
        $query = new SqlQueryBuilder();
        $query->addToQuery('
-- ContentSubgraph::findChildNodes
SELECT c.*, h.name, h.contentstreamidentifier FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeaggregateidentifier = :parentNodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash')
            ->parameter('parentNodeAggregateIdentifier', $parentNodeAggregateIdentifier)
            ->parameter(
                'contentStreamIdentifier',
                (string)$this->getContentStreamIdentifier()
            )
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->hash);

        self::addNodeTypeConstraintsToQuery($query, $nodeTypeConstraints);
        self::addRestrictionRelationConstraintsToQuery(
            $query,
            $this->visibilityConstraints,
            'c'
        );
        $query->addToQuery('ORDER BY h.position ASC');

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAll() as $nodeData) {
            $node = $this->nodeFactory->mapNodeRowToNode(
                $nodeData,
                $this->getDimensionSpacePoint(),
                $this->visibilityConstraints
            );
            $result[] = $node;
            $namedChildNodeCache->add(
                $parentNodeAggregateIdentifier,
                $node->getNodeName(),
                $node
            );
            $parentNodeIdentifierCache->add(
                $node->getNodeAggregateIdentifier(),
                $parentNodeAggregateIdentifier
            );
            $this->inMemoryCache->getNodeByNodeAggregateIdentifierCache()->add(
                $node->getNodeAggregateIdentifier(),
                $node
            );
        }

        //if ($limit === null && $offset === null) { @todo reenable once this is supported
            $cache->add(
                $parentNodeAggregateIdentifier,
                $nodeTypeConstraints,
                $result
            );
        //}

        return Nodes::fromArray($result);
    }

    public function findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface
    {
        $cache = $this->inMemoryCache->getNodeByNodeAggregateIdentifierCache();

        if ($cache->knowsAbout($nodeAggregateIdentifier)) {
            return $cache->get($nodeAggregateIdentifier);
        } else {
            $query = new SqlQueryBuilder();
            $query->addToQuery('
-- ContentSubgraph::findNodeByNodeAggregateIdentifier
SELECT n.*, h.name, h.contentstreamidentifier FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint

 WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 ')
                ->parameter(
                    'nodeAggregateIdentifier',
                    (string)$nodeAggregateIdentifier
                )
                ->parameter(
                    'contentStreamIdentifier',
                    (string)$this->getContentStreamIdentifier()
                )
                ->parameter(
                    'dimensionSpacePointHash',
                    $this->getDimensionSpacePoint()->hash
                );

            $query = self::addRestrictionRelationConstraintsToQuery(
                $query,
                $this->visibilityConstraints
            );

            $nodeRow = $query->execute($this->getDatabaseConnection())->fetch();
            if ($nodeRow === false) {
                $cache->rememberNonExistingNodeAggregateIdentifier($nodeAggregateIdentifier);
                return null;
            }

            $node = $this->nodeFactory->mapNodeRowToNode(
                $nodeRow,
                $this->getDimensionSpacePoint(),
                $this->visibilityConstraints
            );
            $cache->add($nodeAggregateIdentifier, $node);

            return $node;
        }
    }

    private static function addRestrictionRelationConstraintsToQuery(
        SqlQueryBuilder $query,
        VisibilityConstraints $visibilityConstraints,
        string $aliasOfNodeInQuery = 'n',
        string $aliasOfHierarchyEdgeInQuery = 'h',
        string $markerToReplaceInQuery = null
    ): SqlQueryBuilder {
        // TODO: make QueryBuilder immutable
        if (!$visibilityConstraints->isDisabledContentShown()) {
            $query->addToQuery('
                and not exists (
                    select
                        1
                    from
                        neos_contentgraph_restrictionrelation r
                    where
                        r.contentstreamidentifier = ' . $aliasOfHierarchyEdgeInQuery . '.contentstreamidentifier
                        and r.dimensionspacepointhash = ' . $aliasOfHierarchyEdgeInQuery . '.dimensionspacepointhash
                        and r.affectednodeaggregateidentifier = ' . $aliasOfNodeInQuery . '.nodeaggregateidentifier
                )', $markerToReplaceInQuery);
        } else {
            $query->addToQuery('', $markerToReplaceInQuery);
        }

        return $query;
    }

    public function countChildNodes(
        NodeAggregateIdentifier $parentNodeNodeAggregateIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null
    ): int {
        $query = new SqlQueryBuilder();
        $query->addToQuery('SELECT COUNT(*) FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeaggregateidentifier = :parentNodeNodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash')
            ->parameter(
                'parentNodeNodeAggregateIdentifier',
                (string)$parentNodeNodeAggregateIdentifier
            )
            ->parameter(
                'contentStreamIdentifier',
                (string)$this->getContentStreamIdentifier()
            )
            ->parameter(
                'dimensionSpacePointHash',
                $this->getDimensionSpacePoint()->hash
            );

        self::addRestrictionRelationConstraintsToQuery(
            $query,
            $this->visibilityConstraints,
            'c'
        );

        if ($nodeTypeConstraints) {
            self::addNodeTypeConstraintsToQuery($query, $nodeTypeConstraints);
        }

        $res = $query->execute($this->getDatabaseConnection())->fetchColumn(0);
        return (int)$res;
    }

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param PropertyName $name
     * @return Nodes
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findReferencedNodes(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        PropertyName $name = null
    ): Nodes {
        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findReferencedNodes
SELECT d.*, dh.contentstreamidentifier, dh.name FROM neos_contentgraph_hierarchyrelation sh
 INNER JOIN neos_contentgraph_node s ON sh.childnodeanchor = s.relationanchorpoint
 INNER JOIN neos_contentgraph_referencerelation r ON s.relationanchorpoint = r.nodeanchorpoint
 INNER JOIN neos_contentgraph_node d ON r.destinationnodeaggregateidentifier = d.nodeaggregateidentifier
 INNER JOIN neos_contentgraph_hierarchyrelation dh ON dh.childnodeanchor = d.relationanchorpoint
 WHERE s.nodeaggregateidentifier = :nodeAggregateIdentifier
 AND dh.dimensionspacepointhash = :dimensionSpacePointHash
 AND sh.dimensionspacepointhash = :dimensionSpacePointHash
 AND dh.contentstreamidentifier = :contentStreamIdentifier
 AND sh.contentstreamidentifier = :contentStreamIdentifier
'
        )
            ->parameter('nodeAggregateIdentifier', (string)$nodeAggregateIdentifier)
            ->parameter(
                'contentStreamIdentifier',
                (string)$this->contentStreamIdentifier
            )
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->hash)
            ->parameter('name', (string)$name);

        self::addRestrictionRelationConstraintsToQuery(
            $query,
            $this->visibilityConstraints,
            'd',
            'dh'
        );

        if ($name) {
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

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAll() as $nodeData) {
            $result[] = $this->nodeFactory->mapNodeRowToNode(
                $nodeData,
                $this->getDimensionSpacePoint(),
                $this->visibilityConstraints
            );
        }

        return Nodes::fromArray($result);
    }

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param PropertyName $name
     * @return Nodes
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findReferencingNodes(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        PropertyName $name = null
    ) :Nodes {
        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findReferencingNodes
SELECT s.*, sh.contentstreamidentifier, sh.name FROM neos_contentgraph_hierarchyrelation sh
 INNER JOIN neos_contentgraph_node s ON sh.childnodeanchor = s.relationanchorpoint
 INNER JOIN neos_contentgraph_referencerelation r ON s.relationanchorpoint = r.nodeanchorpoint
 INNER JOIN neos_contentgraph_node d ON r.destinationnodeaggregateidentifier = d.nodeaggregateidentifier
 INNER JOIN neos_contentgraph_hierarchyrelation dh ON dh.childnodeanchor = d.relationanchorpoint
 WHERE d.nodeaggregateidentifier = :destinationnodeaggregateidentifier
 AND dh.dimensionspacepointhash = :dimensionSpacePointHash
 AND sh.dimensionspacepointhash = :dimensionSpacePointHash
 AND dh.contentstreamidentifier = :contentStreamIdentifier
 AND sh.contentstreamidentifier = :contentStreamIdentifier
'
        )
            ->parameter(
                'destinationnodeaggregateidentifier',
                (string)$nodeAggregateIdentifier
            )
            ->parameter('contentStreamIdentifier', (string)$this->contentStreamIdentifier)
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->hash)
            ->parameter('name', (string)$name);

        if ($name) {
            $query->addToQuery('AND r.name = :name');
        }

        self::addRestrictionRelationConstraintsToQuery(
            $query,
            $this->visibilityConstraints,
            's',
            'sh'
        );

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAll() as $nodeData) {
            $result[] = $this->nodeFactory->mapNodeRowToNode(
                $nodeData,
                $this->getDimensionSpacePoint(),
                $this->visibilityConstraints
            );
        }

        return Nodes::fromArray($result);
    }

    /**
     * @param NodeAggregateIdentifier $childNodeAggregateIdentifier
     * @return NodeInterface|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findParentNode(NodeAggregateIdentifier $childNodeAggregateIdentifier): ?NodeInterface
    {
        $cache = $this->inMemoryCache->getParentNodeIdentifierByChildNodeIdentifierCache();

        if ($cache->knowsAbout($childNodeAggregateIdentifier)) {
            $possibleParentIdentifier = $cache->get($childNodeAggregateIdentifier);

            if ($possibleParentIdentifier === null) {
                return null;
            } else {
                // we here trigger findNodeByIdentifier,
                // as this might retrieve the Parent Node from the in-memory cache if it has been loaded before
                return $this->findNodeByNodeAggregateIdentifier($possibleParentIdentifier);
            }
        }

        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findParentNode
SELECT p.*, h.contentstreamidentifier, hp.name FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 INNER JOIN neos_contentgraph_hierarchyrelation hp ON hp.childnodeanchor = p.relationanchorpoint
 WHERE c.nodeaggregateidentifier = :childNodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND hp.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 AND hp.dimensionspacepointhash = :dimensionSpacePointHash'
        )
            ->parameter(
                'childNodeAggregateIdentifier',
                (string)$childNodeAggregateIdentifier
            )
            ->parameter('contentStreamIdentifier', (string)$this->contentStreamIdentifier)
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->hash);

        self::addRestrictionRelationConstraintsToQuery(
            $query,
            $this->visibilityConstraints,
            'p'
        );

        $nodeRow = $query->execute($this->getDatabaseConnection())->fetch();

        $node = $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->getDimensionSpacePoint(),
            $this->visibilityConstraints
        ) : null;
        if ($node) {
            $cache->add(
                $childNodeAggregateIdentifier,
                $node->getNodeAggregateIdentifier()
            );

            // we also add the parent node to the NodeAggregateIdentifier => Node cache;
            // as this might improve cache hit rates as well.
            $this->inMemoryCache->getNodeByNodeAggregateIdentifierCache()->add(
                $node->getNodeAggregateIdentifier(),
                $node
            );
        } else {
            $cache->rememberNonExistingParentNode($childNodeAggregateIdentifier);
        }

        return $node;
    }

    /**
     * @param NodePath $path
     * @param NodeAggregateIdentifier $startingNodeAggregateIdentifier
     * @return NodeInterface|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findNodeByPath(
        NodePath $path,
        NodeAggregateIdentifier $startingNodeAggregateIdentifier
    ): ?NodeInterface {
        $currentNode = $this->findNodeByNodeAggregateIdentifier($startingNodeAggregateIdentifier);
        if (!$currentNode) {
            throw new \RuntimeException(
                'Starting Node (identified by ' . $startingNodeAggregateIdentifier . ') does not exist.'
            );
        }
        foreach ($path->getParts() as $edgeName) {
            // identifier exists here :)
            $currentNode = $this->findChildNodeConnectedThroughEdgeName(
                $currentNode->getNodeAggregateIdentifier(),
                $edgeName
            );
            if (!$currentNode) {
                return null;
            }
        }

        return $currentNode;
    }

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $edgeName
     * @return NodeInterface|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findChildNodeConnectedThroughEdgeName(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $edgeName
    ): ?NodeInterface {
        $cache = $this->inMemoryCache->getNamedChildNodeByNodeIdentifierCache();
        if ($cache->contains($parentNodeAggregateIdentifier, $edgeName)) {
            return $cache->get($parentNodeAggregateIdentifier, $edgeName);
        } else {
            $query = new SqlQueryBuilder();
            $query->addToQuery(
                '
-- ContentGraph::findChildNodeConnectedThroughEdgeName
SELECT
    c.*,
    h.name,
    h.contentstreamidentifier
FROM
    neos_contentgraph_node p
INNER JOIN neos_contentgraph_hierarchyrelation h
    ON h.parentnodeanchor = p.relationanchorpoint
INNER JOIN neos_contentgraph_node c
    ON h.childnodeanchor = c.relationanchorpoint
WHERE
    p.nodeaggregateidentifier = :parentNodeAggregateIdentifier
    AND h.contentstreamidentifier = :contentStreamIdentifier
    AND h.dimensionspacepointhash = :dimensionSpacePointHash
    AND h.name = :edgeName'
            )
                ->parameter(
                    'parentNodeAggregateIdentifier',
                    (string)$parentNodeAggregateIdentifier
                )
                ->parameter(
                    'contentStreamIdentifier',
                    (string)$this->contentStreamIdentifier
                )
                ->parameter(
                    'dimensionSpacePointHash',
                    $this->getDimensionSpacePoint()->hash
                )
                ->parameter('edgeName', (string)$edgeName);

            self::addRestrictionRelationConstraintsToQuery(
                $query,
                $this->visibilityConstraints,
                'c'
            );

            $query->addToQuery('ORDER BY h.position LIMIT 1');

            $nodeData = $query->execute($this->getDatabaseConnection())->fetch();

            if ($nodeData) {
                $node = $this->nodeFactory->mapNodeRowToNode(
                    $nodeData,
                    $this->getDimensionSpacePoint(),
                    $this->visibilityConstraints
                );
                $cache->add(
                    $parentNodeAggregateIdentifier,
                    $edgeName,
                    $node
                );
                $this->inMemoryCache->getNodeByNodeAggregateIdentifierCache()->add(
                    $node->getNodeAggregateIdentifier(),
                    $node
                );
                return $node;
            }
        }

        return null;
    }

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findSiblings(
        NodeAggregateIdentifier $sibling,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes {
        $query = new SqlQueryBuilder();
        $query->addToQuery($this->getSiblingBaseQuery() . '
            AND n.nodeaggregateidentifier != :siblingNodeAggregateIdentifier')
            ->parameter('siblingNodeAggregateIdentifier', (string)$sibling)
            ->parameter('contentStreamIdentifier', (string)$this->contentStreamIdentifier)
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->hash);

        if ($nodeTypeConstraints) {
            self::addNodeTypeConstraintsToQuery($query);
        }
        $query->addToQuery(' ORDER BY h.position');
        if ($limit) {
            $query->addToQuery(' LIMIT ' . $limit);
        }
        if ($offset) {
            $query->addToQuery(' OFFSET ' . $offset);
        }

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAll() as $nodeRecord) {
            $result[] = $this->nodeFactory->mapNodeRowToNode(
                $nodeRecord,
                $this->getDimensionSpacePoint(),
                $this->visibilityConstraints
            );
        }

        return Nodes::fromArray($result);
    }

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findPrecedingSiblings(
        NodeAggregateIdentifier $sibling,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes {
        $query = new SqlQueryBuilder();
        $query->addToQuery($this->getSiblingBaseQuery() . '
            AND n.nodeaggregateidentifier != :siblingNodeAggregateIdentifier')
            ->parameter('siblingNodeAggregateIdentifier', (string)$sibling)
            ->parameter('contentStreamIdentifier', (string)$this->contentStreamIdentifier)
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->hash);
        self::addRestrictionRelationConstraintsToQuery($query, $this->visibilityConstraints);

        $query->addToQuery('
    AND h.position < (
        SELECT sibh.position FROM neos_contentgraph_hierarchyrelation sibh
            INNER JOIN neos_contentgraph_node sib ON sibh.childnodeanchor = sib.relationanchorpoint
        WHERE sib.nodeaggregateidentifier = :siblingNodeAggregateIdentifier
            AND sibh.contentstreamidentifier = :contentStreamIdentifier
            AND sibh.dimensionspacepointhash = :dimensionSpacePointHash
    )');

        if ($nodeTypeConstraints) {
            self::addNodeTypeConstraintsToQuery($query);
        }
        $query->addToQuery(' ORDER BY h.position DESC');
        if ($limit) {
            $query->addToQuery(' LIMIT ' . $limit);
        }
        if ($offset) {
            $query->addToQuery(' OFFSET ' . $offset);
        }

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAll() as $nodeRecord) {
            $result[] = $this->nodeFactory->mapNodeRowToNode(
                $nodeRecord,
                $this->getDimensionSpacePoint(),
                $this->visibilityConstraints
            );
        }

        return Nodes::fromArray($result);
    }

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findSucceedingSiblings(
        NodeAggregateIdentifier $sibling,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes {
        $query = new SqlQueryBuilder();
        $query->addToQuery($this->getSiblingBaseQuery() . '
            AND n.nodeaggregateidentifier != :siblingNodeAggregateIdentifier')
            ->parameter('siblingNodeAggregateIdentifier', (string)$sibling)
            ->parameter('contentStreamIdentifier', (string)$this->contentStreamIdentifier)
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->hash);
        self::addRestrictionRelationConstraintsToQuery($query, $this->visibilityConstraints);

        $query->addToQuery('
    AND h.position > (
        SELECT sibh.position FROM neos_contentgraph_hierarchyrelation sibh
            INNER JOIN neos_contentgraph_node sib ON sibh.childnodeanchor = sib.relationanchorpoint
        WHERE sib.nodeaggregateidentifier = :siblingNodeAggregateIdentifier
            AND sibh.contentstreamidentifier = :contentStreamIdentifier
            AND sibh.dimensionspacepointhash = :dimensionSpacePointHash
    )');

        if ($nodeTypeConstraints) {
            self::addNodeTypeConstraintsToQuery($query);
        }
        $query->addToQuery(' ORDER BY h.position ASC');
        if ($limit) {
            $query->addToQuery(' LIMIT ' . $limit);
        }
        if ($offset) {
            $query->addToQuery(' OFFSET ' . $offset);
        }

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAll() as $nodeRecord) {
            $result[] = $this->nodeFactory->mapNodeRowToNode(
                $nodeRecord,
                $this->getDimensionSpacePoint(),
                $this->visibilityConstraints
            );
        }

        return Nodes::fromArray($result);
    }

    protected function getSiblingBaseQuery(): string
    {
        return '
  SELECT n.*, h.contentstreamidentifier, h.name FROM neos_contentgraph_node n
  INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
  WHERE h.contentstreamidentifier = :contentStreamIdentifier AND h.dimensionspacepointhash = :dimensionSpacePointHash
  AND h.parentnodeanchor = (
      SELECT sibh.parentnodeanchor FROM neos_contentgraph_hierarchyrelation sibh
        INNER JOIN neos_contentgraph_node sib ON sibh.childnodeanchor = sib.relationanchorpoint
      WHERE sib.nodeaggregateidentifier = :siblingNodeAggregateIdentifier
        AND sibh.contentstreamidentifier = :contentStreamIdentifier
        AND sibh.dimensionspacepointhash = :dimensionSpacePointHash
  )';
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }


    public function findNodePath(NodeAggregateIdentifier $nodeAggregateIdentifier): NodePath
    {
        $cache = $this->inMemoryCache->getNodePathCache();

        if ($cache->contains($nodeAggregateIdentifier)) {
            /** @var NodePath $nodePath */
            $nodePath = $cache->get($nodeAggregateIdentifier);
            return $nodePath;
        }

        $result = $this->getDatabaseConnection()->executeQuery(
            '
            -- ContentSubgraph::findNodePath
            with recursive nodePath as (
            SELECT h.name, h.parentnodeanchor FROM neos_contentgraph_node n
                 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                 AND h.contentstreamidentifier = :contentStreamIdentifier
                 AND h.dimensionspacepointhash = :dimensionSpacePointHash
                 AND n.nodeaggregateidentifier = :nodeAggregateIdentifier

            UNION

                SELECT h.name, h.parentnodeanchor FROM neos_contentgraph_hierarchyrelation h
                    INNER JOIN nodePath as np ON h.childnodeanchor = np.parentnodeanchor
                    WHERE
                        h.contentstreamidentifier = :contentStreamIdentifier
                        AND h.dimensionspacepointhash = :dimensionSpacePointHash

        )
        select * from nodePath',
            [
                'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
                'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->hash,
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier
            ]
        )->fetchAll();

        $nodePathSegments = [];

        foreach ($result as $r) {
            $nodePathSegments[] = $r['name'];
        }

        $nodePathSegments = array_reverse($nodePathSegments);
        $nodePath = NodePath::fromPathSegments($nodePathSegments);
        $cache->add($nodeAggregateIdentifier, $nodePath);

        return $nodePath;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'dimensionSpacePoint' => $this->dimensionSpacePoint
        ];
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Neos\ContentRepository\Exception\NodeConfigurationException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function findSubtrees(
        NodeAggregateIdentifiers $entryNodeAggregateIdentifiers,
        int $maximumLevels,
        NodeTypeConstraints $nodeTypeConstraints
    ): SubtreeInterface {
        $query = new SqlQueryBuilder();
        $query->addToQuery('
-- ContentSubgraph::findSubtrees

-- we build a set of recursive trees, ready to be rendered e.g. in a menu.
-- Because the menu supports starting at multiple nodes, we also support starting at multiple nodes at once.
with recursive tree as (
     -- --------------------------------
     -- INITIAL query: select the root nodes of the tree; as given in $menuLevelNodeIdentifiers
     -- --------------------------------
     select
     	n.*,
     	h.contentstreamidentifier,
     	h.name,

     	-- see
     	-- https://mariadb.com/kb/en/library/recursive-common-table-expressions-overview/#cast-to-avoid-data-truncation
     	CAST("ROOT" AS CHAR(50)) as parentNodeAggregateIdentifier,
     	0 as level,
     	0 as position
     from
        neos_contentgraph_node n
     -- we need to join with the hierarchy relation, because we need the node name.
     inner join neos_contentgraph_hierarchyrelation h
        on h.childnodeanchor = n.relationanchorpoint
     where
        n.nodeaggregateidentifier in (:entryNodeAggregateIdentifiers)
        and h.contentstreamidentifier = :contentStreamIdentifier
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
		###VISIBILITY_CONSTRAINTS_INITIAL###
union
     -- --------------------------------
     -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
     -- --------------------------------
     select
        c.*,
        h.contentstreamidentifier,
        h.name,

     	p.nodeaggregateidentifier as parentNodeAggregateIdentifier,
     	p.level + 1 as level,
     	h.position
     from
        tree p
	 inner join neos_contentgraph_hierarchyrelation h
        on h.parentnodeanchor = p.relationanchorpoint
	 inner join neos_contentgraph_node c
	    on h.childnodeanchor = c.relationanchorpoint
	 where
	 	h.contentstreamidentifier = :contentStreamIdentifier
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
		and p.level + 1 <= :maximumLevels
        ###NODE_TYPE_CONSTRAINTS###
        ###VISIBILITY_CONSTRAINTS_RECURSION###

   -- select relationanchorpoint from neos_contentgraph_node
)
select * from tree
order by level asc, position asc;')
            ->parameter(
                'entryNodeAggregateIdentifiers',
                $entryNodeAggregateIdentifiers->toStringArray(),
                Connection::PARAM_STR_ARRAY
            )
            ->parameter('contentStreamIdentifier', (string)$this->contentStreamIdentifier)
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->hash)
            ->parameter('maximumLevels', $maximumLevels);

        self::addNodeTypeConstraintsToQuery(
            $query,
            $nodeTypeConstraints,
            '###NODE_TYPE_CONSTRAINTS###'
        );

        self::addRestrictionRelationConstraintsToQuery(
            $query,
            $this->visibilityConstraints,
            'n',
            'h',
            '###VISIBILITY_CONSTRAINTS_INITIAL###'
        );
        self::addRestrictionRelationConstraintsToQuery(
            $query,
            $this->visibilityConstraints,
            'c',
            'h',
            '###VISIBILITY_CONSTRAINTS_RECURSION###'
        );

        $result = $query->execute($this->getDatabaseConnection())->fetchAll();

        $subtreesByNodeIdentifier = [];
        $subtreesByNodeIdentifier['ROOT'] = new Subtree(0);

        foreach ($result as $nodeData) {
            $node = $this->nodeFactory->mapNodeRowToNode(
                $nodeData,
                $this->getDimensionSpacePoint(),
                $this->visibilityConstraints
            );
            $this->getInMemoryCache()->getNodeByNodeAggregateIdentifierCache()->add(
                $node->getNodeAggregateIdentifier(),
                $node
            );

            if (!isset($subtreesByNodeIdentifier[$nodeData['parentNodeAggregateIdentifier']])) {
                throw new \Exception('TODO: must not happen');
            }

            $subtree = new Subtree((int)$nodeData['level'], $node);
            $subtreesByNodeIdentifier[$nodeData['parentNodeAggregateIdentifier']]->add($subtree);
            $subtreesByNodeIdentifier[$nodeData['nodeaggregateidentifier']] = $subtree;

            // also add the parents to the child -> parent cache.
            /* @var $parentSubtree Subtree */
            $parentSubtree = $subtreesByNodeIdentifier[$nodeData['parentNodeAggregateIdentifier']];
            if ($parentSubtree->getNode() !== null) {
                $this->getInMemoryCache()->getParentNodeIdentifierByChildNodeIdentifierCache()->add(
                    $node->getNodeAggregateIdentifier(),
                    $parentSubtree->getNode()->getNodeAggregateIdentifier()
                );
            }
        }

        return $subtreesByNodeIdentifier['ROOT'];
    }

    /**
     * @param array<int|string,NodeAggregateIdentifier> $entryNodeAggregateIdentifiers
     * @throws \Doctrine\DBAL\Exception
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function findDescendants(
        array $entryNodeAggregateIdentifiers,
        NodeTypeConstraints $nodeTypeConstraints,
        ?\Neos\ContentRepository\Projection\Content\SearchTerm $searchTerm
    ): Nodes {
        $query = new SqlQueryBuilder();
        $query->addToQuery('
-- ContentSubgraph::findDescendants

-- we find all nodes matching the given constraints that are descendants of one of the given aggregates
with recursive tree as (
     -- --------------------------------
     -- INITIAL query: select the entry nodes
     -- --------------------------------
     select
     	n.*,
     	h.contentstreamidentifier,
     	h.name,

     	-- see
     	-- https://mariadb.com/kb/en/library/recursive-common-table-expressions-overview/#cast-to-avoid-data-truncation
     	CAST("ROOT" AS CHAR(50)) as parentNodeAggregateIdentifier,
     	0 as level,
     	0 as position
     from
        neos_contentgraph_node n
     -- we need to join with the hierarchy relation, because we need the node name.
     INNER JOIN neos_contentgraph_hierarchyrelation h
        ON h.childnodeanchor = n.relationanchorpoint
     INNER JOIN neos_contentgraph_node p
        ON p.relationanchorpoint = h.parentnodeanchor
     INNER JOIN neos_contentgraph_hierarchyrelation ph
        on ph.childnodeanchor = p.relationanchorpoint
     WHERE
        p.nodeaggregateidentifier in (:entryNodeAggregateIdentifiers)
        AND h.contentstreamidentifier = :contentStreamIdentifier
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
		AND ph.contentstreamidentifier = :contentStreamIdentifier
		AND ph.dimensionspacepointhash = :dimensionSpacePointHash
		###VISIBILITY_CONSTRAINTS_INITIAL###
union
     -- --------------------------------
     -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
     -- --------------------------------
     select
        c.*,
        h.contentstreamidentifier,
        h.name,

     	p.nodeaggregateidentifier as parentNodeAggregateIdentifier,
     	p.level + 1 as level,
     	h.position
     from
        tree p
	 inner join neos_contentgraph_hierarchyrelation h
        on h.parentnodeanchor = p.relationanchorpoint
	 inner join neos_contentgraph_node c
	    on h.childnodeanchor = c.relationanchorpoint
	 where
	 	h.contentstreamidentifier = :contentStreamIdentifier
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
        ###VISIBILITY_CONSTRAINTS_RECURSION###

   -- select relationanchorpoint from neos_contentgraph_node
)
select * from tree
where
    1=1
    ###NODE_TYPE_CONSTRAINTS###
    ###SEARCH_TERM_CONSTRAINTS###
order by level asc, position asc;')
            ->parameter('entryNodeAggregateIdentifiers', array_map(
                function (NodeAggregateIdentifier $nodeAggregateIdentifier): string {
                    return (string)$nodeAggregateIdentifier;
                },
                $entryNodeAggregateIdentifiers
            ), Connection::PARAM_STR_ARRAY)
            ->parameter('contentStreamIdentifier', (string)$this->contentStreamIdentifier)
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->hash);

        self::addNodeTypeConstraintsToQuery(
            $query,
            $nodeTypeConstraints,
            '###NODE_TYPE_CONSTRAINTS###',
            ''
        );
        self::addSearchTermConstraintsToQuery(
            $query,
            $searchTerm,
            '###SEARCH_TERM_CONSTRAINTS###',
            ''
        );
        self::addRestrictionRelationConstraintsToQuery(
            $query,
            $this->visibilityConstraints,
            'n',
            'h',
            '###VISIBILITY_CONSTRAINTS_INITIAL###'
        );
        self::addRestrictionRelationConstraintsToQuery(
            $query,
            $this->visibilityConstraints,
            'c',
            'h',
            '###VISIBILITY_CONSTRAINTS_RECURSION###'
        );

        // TODO: maybe make Nodes lazy-capable as well (so we can yield the results inside the foreach loop)
        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAll() as $nodeRecord) {
            $result[] = $this->nodeFactory->mapNodeRowToNode(
                $nodeRecord,
                $this->getDimensionSpacePoint(),
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
        $query->addToQuery('
SELECT COUNT(*) FROM neos_contentgraph_node n
 JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash')
            ->parameter('contentStreamIdentifier', (string)$this->contentStreamIdentifier)
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->hash);

        return (int) $query->execute($this->getDatabaseConnection())->fetch()['COUNT(*)'];
    }

    public function getInMemoryCache(): InMemoryCache
    {
        return $this->inMemoryCache;
    }
}
