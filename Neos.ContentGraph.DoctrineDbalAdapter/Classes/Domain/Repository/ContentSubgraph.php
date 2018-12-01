<?php

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
use Neos\ContentRepository\Domain\Factory\NodeTypeConstraintFactory;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\InMemoryCache;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\EventSourcedContentRepository\Domain as ContentRepository;
use Neos\EventSourcedContentRepository\Domain\Context\Node\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeConstraints;
use Neos\Flow\Annotations as Flow;

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
final class ContentSubgraph implements ContentProjection\ContentSubgraphInterface
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
     * @var ContentRepository\Context\Parameters\VisibilityConstraints
     */
    protected $visibilityConstraints;

    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint, ContentRepository\Context\Parameters\VisibilityConstraints $visibilityConstraints)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->visibilityConstraints = $visibilityConstraints;
        $this->inMemoryCache = new InMemoryCache();
    }

    /**
     * @param SqlQueryBuilder $query
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param string|null $markerToReplaceInQuery
     */
    protected static function addNodeTypeConstraintsToQuery(
        SqlQueryBuilder $query,
        NodeTypeConstraints $nodeTypeConstraints = null,
        string $markerToReplaceInQuery = null
    ): void {
        if ($nodeTypeConstraints) {
            if (!empty($nodeTypeConstraints->getExplicitlyAllowedNodeTypeNames())) {
                $allowanceQueryPart = 'c.nodetypename IN (:explicitlyAllowedNodeTypeNames)';
                $query->parameter('explicitlyAllowedNodeTypeNames', $nodeTypeConstraints->getExplicitlyAllowedNodeTypeNames(), Connection::PARAM_STR_ARRAY);
            } else {
                $allowanceQueryPart = '';
            }
            if (!empty($nodeTypeConstraints->getExplicitlyDisallowedNodeTypeNames())) {
                $disAllowanceQueryPart = 'c.nodetypename NOT IN (:explicitlyDisallowedNodeTypeNames)';
                $query->parameter('explicitlyDisallowedNodeTypeNames', $nodeTypeConstraints->getExplicitlyDisallowedNodeTypeNames(), Connection::PARAM_STR_ARRAY);
            } else {
                $disAllowanceQueryPart = '';
            }

            if ($allowanceQueryPart && $disAllowanceQueryPart) {
                $query->addToQuery(' AND (' . $allowanceQueryPart . ($nodeTypeConstraints->isWildcardAllowed() ? ' OR ' : ' AND ') . $disAllowanceQueryPart . ')', $markerToReplaceInQuery);
            } elseif ($allowanceQueryPart && !$nodeTypeConstraints->isWildcardAllowed()) {
                $query->addToQuery(' AND ' . $allowanceQueryPart, $markerToReplaceInQuery);
            } elseif ($disAllowanceQueryPart) {
                $query->addToQuery(' AND ' . $disAllowanceQueryPart, $markerToReplaceInQuery);
            } else {
                $query->addToQuery('', $markerToReplaceInQuery);
            }
        }
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
     * @param NodeIdentifier $nodeIdentifier
     * @return NodeInterface|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeConfigurationException
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeTypeNotFoundException
     */
    public function findNodeByIdentifier(NodeIdentifier $nodeIdentifier): ?NodeInterface
    {
        $cache = $this->inMemoryCache->getNodeByNodeIdentifierCache();
        if ($cache->knowsAbout($nodeIdentifier)) {
            return $cache->get($nodeIdentifier);
        } else {
            $query = new SqlQueryBuilder();
            $query->addToQuery('
-- ContentSubgraph::findNodeByIdentifier
SELECT n.*, h.name, h.contentstreamidentifier, h.dimensionspacepoint FROM neos_contentgraph_node n
    INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
    WHERE n.nodeidentifier = :nodeIdentifier
    AND h.contentstreamidentifier = :contentStreamIdentifier       
    AND h.dimensionspacepointhash = :dimensionSpacePointHash
')
                ->parameter('nodeIdentifier', (string)$nodeIdentifier)
                ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
                ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash());

            self::addRestrictionEdgeConstraintsToQuery($query, $this->visibilityConstraints);

            $nodeRow = $query->execute($this->getDatabaseConnection())->fetch();

            if (is_array($nodeRow)) {
                $node = $this->nodeFactory->mapNodeRowToNode($nodeRow);
                $cache->add($nodeIdentifier, $node);

                return $node;
            } else {
                $cache->rememberNonExistingNodeIdentifier($nodeIdentifier);

                return null;
            }
        }
    }

    /**
     * @param NodeIdentifier $parentNodeIdentifier
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     * @throws \Exception
     */
    public function findChildNodes(
        NodeIdentifier $parentNodeIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): array {
        if ($limit !== null || $offset !== null) {
            throw new \RuntimeException("TODO: Limit/Offset not yet supported in findChildNodes");
        }

        $cache = $this->inMemoryCache->getAllChildNodesByNodeIdentifierCache();
        $namedChildNodeCache = $this->inMemoryCache->getNamedChildNodeByNodeIdentifierCache();
        $parentNodeIdentifierCache = $this->inMemoryCache->getParentNodeIdentifierByChildNodeIdentifierCache();

        if ($cache->contains($parentNodeIdentifier)) {
            return $cache->findChildNodes($parentNodeIdentifier, $nodeTypeConstraints, $limit, $offset);
        }
        $query = new SqlQueryBuilder();
        $query->addToQuery('
-- ContentSubgraph::findChildNodes
SELECT c.*, h.name, h.contentstreamidentifier, h.dimensionspacepoint FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeidentifier = :parentNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash')
            ->parameter('parentNodeIdentifier', $parentNodeIdentifier)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash());

        self::addNodeTypeConstraintsToQuery($query, $nodeTypeConstraints);
        self::addRestrictionEdgeConstraintsToQuery($query, $this->visibilityConstraints, 'c');
        $query->addToQuery('ORDER BY h.position ASC');

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAll() as $nodeData) {
            $node = $this->nodeFactory->mapNodeRowToNode($nodeData);
            $result[] = $node;
            $namedChildNodeCache->add($parentNodeIdentifier, $node->getNodeName(), $node);
            $parentNodeIdentifierCache->add($node->getNodeIdentifier(), $parentNodeIdentifier);
        }

        if ($nodeTypeConstraints === null && $limit === null && $offset === null) {
            $cache->add($parentNodeIdentifier, $result);
        }

        return $result;
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
SELECT n.*, h.name, h.contentstreamidentifier, h.dimensionspacepoint FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 
 WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 ')
                ->parameter('nodeAggregateIdentifier', (string)$nodeAggregateIdentifier)
                ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
                ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash());

            $query = self::addRestrictionEdgeConstraintsToQuery($query, $this->visibilityConstraints);

            $nodeRow = $query->execute($this->getDatabaseConnection())->fetch();
            if ($nodeRow === false) {
                $cache->rememberNonExistingNodeAggregateIdentifier($nodeAggregateIdentifier);
                return null;
            }

            $node = $this->nodeFactory->mapNodeRowToNode($nodeRow);
            $cache->add($nodeAggregateIdentifier, $node);

            return $node;
        }
    }

    private static function addRestrictionEdgeConstraintsToQuery(SqlQueryBuilder $query, ContentRepository\Context\Parameters\VisibilityConstraints $visibilityConstraints, string $aliasOfNodeInQuery = 'n', string $aliasOfHierarchyEdgeInQuery = 'h', $markerToReplaceInQuery = null): SqlQueryBuilder
    {
        // TODO: make QueryBuilder immutable
        if (!$visibilityConstraints->isInvisibleContentShown()) {
            $query->addToQuery('
                and not exists (
                    select
                        1 
                    from
                        neos_contentgraph_restrictionedge r
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
        NodeIdentifier $parentNodeIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null
    ): int {
        $query = new SqlQueryBuilder();
        $query->addToQuery('SELECT COUNT(c.nodeidentifier) FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeidentifier = :parentNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash')
            ->parameter('parentNodeIdentifier', (string)$parentNodeIdentifier)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash());

        self::addRestrictionEdgeConstraintsToQuery($query, $this->visibilityConstraints, 'c');

        if ($nodeTypeConstraints) {
            self::addNodeTypeConstraintsToQuery($query, $nodeTypeConstraints);
        }

        $res = $query->execute($this->getDatabaseConnection())->fetchColumn(0);
        return $res;
    }

    /**
     * @param NodeIdentifier $nodeIdentifier
     * @param PropertyName $name
     * @return NodeInterface[]
     */
    public function findReferencedNodes(NodeIdentifier $nodeIdentifier, PropertyName $name = null): array
    {
        $query = new SqlQueryBuilder();
        $query->addToQuery('
-- ContentSubgraph::findReferencedNodes
SELECT d.*, dh.contentstreamidentifier, dh.name, dh.dimensionspacepoint FROM neos_contentgraph_hierarchyrelation sh
 INNER JOIN neos_contentgraph_node s ON sh.childnodeanchor = s.relationanchorpoint 
 INNER JOIN neos_contentgraph_referencerelation r ON s.relationanchorpoint = r.nodeanchorpoint
 INNER JOIN neos_contentgraph_node d ON r.destinationnodeaggregateidentifier = d.nodeaggregateidentifier
 INNER JOIN neos_contentgraph_hierarchyrelation dh ON dh.childnodeanchor = d.relationanchorpoint  
 WHERE s.nodeidentifier = :nodeIdentifier
 AND dh.dimensionspacepointhash = :dimensionSpacePointHash
 AND sh.dimensionspacepointhash = :dimensionSpacePointHash
 AND dh.contentstreamidentifier = :contentStreamIdentifier
 AND sh.contentstreamidentifier = :contentStreamIdentifier
'
        )
            ->parameter('nodeIdentifier', (string)$nodeIdentifier)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', (string)$this->getDimensionSpacePoint()->getHash())
            ->parameter('name', (string)$name);

        self::addRestrictionEdgeConstraintsToQuery($query, $this->visibilityConstraints, 'd', 'dh');

        if ($name) {
            $query->addToQuery('
 AND r.name = :name
 ORDER BY r.position'
            );
        } else {
            $query->addToQuery('
 ORDER BY r.name, r.position'
            );
        }

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAll() as $nodeData) {
            $result[] = $this->nodeFactory->mapNodeRowToNode($nodeData);
        }

        return $result;
    }

    /**
     * @param NodeIdentifier $nodeIdentifier
     * @param PropertyName $name
     * @return NodeInterface[]
     */
    public function findReferencingNodes(NodeIdentifier $nodeIdentifier, PropertyName $name = null) :array
    {
        $node = $this->findNodeByIdentifier($nodeIdentifier);

        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findReferencingNodes
SELECT s.*, sh.contentstreamidentifier, sh.name, sh.dimensionspacepoint FROM neos_contentgraph_hierarchyrelation sh
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
            ->parameter('destinationnodeaggregateidentifier', (string)$node->getNodeAggregateIdentifier())
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', (string)$this->getDimensionSpacePoint()->getHash())
            ->parameter('name', (string)$name);

        if ($name) {
            $query->addToQuery('AND r.name = :name');
        }

        self::addRestrictionEdgeConstraintsToQuery($query, $this->visibilityConstraints, 's', 'sh');

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAll() as $nodeData) {
            $result[] = $this->nodeFactory->mapNodeRowToNode($nodeData);
        }

        return $result;
    }

    /**
     * @param NodeIdentifier $childNodeIdentifier
     * @return NodeInterface|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeConfigurationException
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeTypeNotFoundException
     */
    public function findParentNode(NodeIdentifier $childNodeIdentifier): ?NodeInterface
    {
        $cache = $this->inMemoryCache->getParentNodeIdentifierByChildNodeIdentifierCache();


        if ($cache->knowsAbout($childNodeIdentifier)) {
            $possibleParentIdentifier = $cache->get($childNodeIdentifier);

            if ($possibleParentIdentifier === null) {
                return null;
            } else {
                // we here trigger findNodeByIdentifier, as this might retrieve the Parent Node from the in-memory cache if it has been loaded before
                return $this->findNodeByIdentifier($possibleParentIdentifier);
            }
        }

        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findParentNode
SELECT p.*, h.contentstreamidentifier, hp.name, hp.dimensionspacepoint FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 INNER JOIN neos_contentgraph_hierarchyrelation hp ON hp.childnodeanchor = p.relationanchorpoint
 WHERE c.nodeidentifier = :childNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND hp.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 AND hp.dimensionspacepointhash = :dimensionSpacePointHash'
        )
            ->parameter('childNodeIdentifier', (string)$childNodeIdentifier)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash());

        self::addRestrictionEdgeConstraintsToQuery($query, $this->visibilityConstraints, 'p');

        $nodeRow = $query->execute($this->getDatabaseConnection())->fetch();

        $node = $nodeRow ? $this->nodeFactory->mapNodeRowToNode($nodeRow) : null;
        if ($node) {
            $cache->add($childNodeIdentifier, $node->getNodeIdentifier());

            // we also add the parent node to the NodeIdentifier => Node cache; as this might improve cache hit rates as well.
            $this->inMemoryCache->getNodeByNodeIdentifierCache()->add($node->getNodeIdentifier(), $node);
        } else {
            $cache->rememberNonExistingParentNode($childNodeIdentifier);
        }

        return $node;
    }

    /**
     * @param NodeAggregateIdentifier $childNodeAggregateIdentifier
     * @return NodeInterface|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeConfigurationException
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeTypeNotFoundException
     */
    public function findParentNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $childNodeAggregateIdentifier): ?NodeInterface
    {
        $query = new SqlQueryBuilder();
        $query->addToQuery(
            '
-- ContentSubgraph::findParentNodeByNodeAggregateIdentifier
SELECT p.*, h.contentstreamidentifier, hp.name, hp.dimensionspacepoint FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 INNER JOIN neos_contentgraph_hierarchyrelation hp ON hp.childnodeanchor = p.relationanchorpoint
 WHERE c.nodeaggregateidentifier = :childNodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND hp.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 AND hp.dimensionspacepointhash = :dimensionSpacePointHash'
        )
            ->parameter('childNodeAggregateIdentifier', (string)$childNodeAggregateIdentifier)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash());

        self::addRestrictionEdgeConstraintsToQuery($query, $this->visibilityConstraints, 'p');

        $nodeRow = $query->execute($this->getDatabaseConnection())->fetch();

        $node = $nodeRow ? $this->nodeFactory->mapNodeRowToNode($nodeRow) : null;

        return $node;
    }

    /**
     * @param string $path
     * @param NodeIdentifier $startingNodeIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByPath(string $path, NodeIdentifier $startingNodeIdentifier): ?NodeInterface
    {
        $currentNode = $this->findNodeByIdentifier($startingNodeIdentifier);
        if (!$currentNode) {
            throw new \RuntimeException('Starting Node (identified by ' . $startingNodeIdentifier . ') does not exist.');
        }
        $edgeNames = explode('/', trim($path, '/'));
        if ($edgeNames !== [""]) {
            foreach ($edgeNames as $edgeName) {
                // identifier exists here :)
                $currentNode = $this->findChildNodeConnectedThroughEdgeName($currentNode->getNodeIdentifier(),
                    new NodeName($edgeName));
                if (!$currentNode) {
                    return null;
                }
            }
        }

        return $currentNode;
    }

    /**
     * @param NodeIdentifier $parentNodeIdentifier
     * @param NodeName $edgeName
     * @return NodeInterface|null
     */
    public function findChildNodeConnectedThroughEdgeName(
        NodeIdentifier $parentNodeIdentifier,
        NodeName $edgeName
    ): ?NodeInterface {
        $cache = $this->inMemoryCache->getNamedChildNodeByNodeIdentifierCache();
        if ($cache->contains($parentNodeIdentifier, $edgeName)) {
            return $cache->get($parentNodeIdentifier, $edgeName);
        } else {
            $params = [
                'parentNodeIdentifier' => (string)$parentNodeIdentifier,
                'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
                'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash(),
                'edgeName' => (string)$edgeName
            ];

            $query = new SqlQueryBuilder();
            $query->addToQuery(
                '
-- ContentGraph::findChildNodeConnectedThroughEdgeName
SELECT
    c.*,
    h.name, 
    h.contentstreamidentifier,
    h.dimensionspacepoint
FROM
    neos_contentgraph_node p
INNER JOIN neos_contentgraph_hierarchyrelation h
    ON h.parentnodeanchor = p.relationanchorpoint
INNER JOIN neos_contentgraph_node c
    ON h.childnodeanchor = c.relationanchorpoint
WHERE
    p.nodeidentifier = :parentNodeIdentifier
    AND h.contentstreamidentifier = :contentStreamIdentifier
    AND h.dimensionspacepointhash = :dimensionSpacePointHash
    AND h.name = :edgeName'
            )
                ->parameter('parentNodeIdentifier', (string)$parentNodeIdentifier)
                ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
                ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash())
                ->parameter('edgeName', (string)$edgeName);

            self::addRestrictionEdgeConstraintsToQuery($query, $this->visibilityConstraints, 'c');

            $query->addToQuery('ORDER BY h.position LIMIT 1');

            $nodeData = $query->execute($this->getDatabaseConnection())->fetch();

            if ($nodeData) {
                $node = $this->nodeFactory->mapNodeRowToNode($nodeData);
                if ($node) {
                    $cache->add($parentNodeIdentifier, $edgeName, $node);

                    return $node;
                }
            }
        }

        return null;
    }

    /**
     * @param NodeAggregateIdentifier $parentAggregateIdentifier
     * @param NodeName $edgeName
     * @return ContentRepository\Model\NodeInterface|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeConfigurationException
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeTypeNotFoundException
     */
    public function findChildNodeByNodeAggregateIdentifierConnectedThroughEdgeName(
        NodeAggregateIdentifier $parentAggregateIdentifier,
        NodeName $edgeName
    ): ?NodeInterface {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT c.*, h.name, h.contentstreamidentifier, h.dimensionspacepoint FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeaggregateidentifier = :parentNodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 AND h.name = :edgeName
 ORDER BY h.position LIMIT 1',
            [
                'parentNodeAggregateIdentifier' => (string)$parentAggregateIdentifier,
                'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
                'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash(),
                'edgeName' => (string)$edgeName
            ]
        )->fetch();

        return $nodeData ? $this->nodeFactory->mapNodeRowToNode($nodeData) : null;
    }

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findSiblings(NodeAggregateIdentifier $sibling, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array
    {
        $query = new SqlQueryBuilder();
        $query->addToQuery($this->getSiblingBaseQuery() . '
            AND n.nodeaggregateidentier != :siblingNodeAggregateIdentifier')
            ->parameter('siblingNodeAggregateIdentifier', (string)$sibling)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash());

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
            $result[] = $this->nodeFactory->mapNodeRowToNode($nodeRecord);
        }

        return $result;
    }

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findPrecedingSiblings(
        NodeAggregateIdentifier $sibling,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): array {
        $query = new SqlQueryBuilder();
        $query->addToQuery($this->getSiblingBaseQuery() . '
            AND n.nodeaggregateidentier != :siblingNodeAggregateIdentifier')
            ->parameter('siblingNodeAggregateIdentifier', (string)$sibling)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash());
        $query->addToQuery('
    AND h.position < (
        SELECT sibh.position FROM neos_contentgraph_hierarchyrelation sibh
        INNER JOIN neos_contentgraph_node sib ON sibh.childnodeanchor = sib.relationanchorpoint
        WHERE sib.nodeaggregateidentifier = :siblingNodeAggregateIdentifier
        AND sibh.contentstreamidentifier = :contentStreamIdentifier AND sibh.dimensionspacepointhash = :dimensionSpacePointHash
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
            $result[] = $this->nodeFactory->mapNodeRowToNode($nodeRecord);
        }

        return $result;
    }

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findSucceedingSiblings(
        NodeAggregateIdentifier $sibling,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): array {
        $query = new SqlQueryBuilder();
        $query->addToQuery($this->getSiblingBaseQuery() . '
            AND n.nodeaggregateidentier != :siblingNodeAggregateIdentifier')
            ->parameter('siblingNodeAggregateIdentifier', (string)$sibling)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash());
        $query->addToQuery('
    AND h.position > (
        SELECT sibh.position FROM neos_contentgraph_hierarchyrelation sibh
        INNER JOIN neos_contentgraph_node sib ON sibh.childnodeanchor = sib.relationanchorpoint
        WHERE sib.nodeaggregateidentifier = :siblingNodeAggregateIdentifier
        AND sibh.contentstreamidentifier = :contentStreamIdentifier AND sibh.dimensionspacepointhash = :dimensionSpacePointHash
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
            $result[] = $this->nodeFactory->mapNodeRowToNode($nodeRecord);
        }

        return $result;
    }

    protected function getSiblingBaseQuery(): string
    {
        return '
  SELECT n.*, h.contentstreamidentifier, h.name, h.dimensionspacepoint FROM neos_contentgraph_node n
  INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
  WHERE h.contentstreamidentifier = :contentStreamIdentifier AND h.dimensionspacepointhash = :dimensionSpacePointHash
  AND h.parentnodeanchor = (
      SELECT sibh.parentnodeanchor FROM neos_contentgraph_hierarchyrelation sibh
      INNER JOIN neos_contentgraph_node sib ON sibh.childnodeanchor = sib.relationanchorpoint
      WHERE sib.nodeaggregateidentifier = :siblingNodeAggregateIdentifier
      AND sibh.contentstreamidentifier = :contentStreamIdentifier AND sibh.dimensionspacepointhash = :dimensionSpacePointHash
  )';
    }

    /**
     * @param NodeInterface $startNode
     * @param ContentProjection\HierarchyTraversalDirection $direction
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param callable $callback
     * @throws \Exception
     */
    public function traverseHierarchy(
        NodeInterface $startNode,
        ContentProjection\HierarchyTraversalDirection $direction = null,
        NodeTypeConstraints $nodeTypeConstraints = null,
        callable $callback
    ): void {
        if (is_null($direction)) {
            $direction = ContentProjection\HierarchyTraversalDirection::down();
        }

        $continueTraversal = $callback($startNode);
        if ($continueTraversal) {
            if ($direction->isUp()) {
                $parentNode = $this->findParentNode($startNode->getNodeIdentifier());
                if ($parentNode && ($nodeTypeConstraints === null || $nodeTypeConstraints->matches($parentNode->getNodeTypeName()))) {
                    $this->traverseHierarchy($parentNode, $direction, $nodeTypeConstraints, $callback);
                }
            } elseif ($direction->isDown()) {
                foreach ($this->findChildNodes(
                    $startNode->getNodeIdentifier(),
                    $nodeTypeConstraints,
                    null,
                    null
                ) as $childNode) {
                    $this->traverseHierarchy($childNode, $direction, $nodeTypeConstraints, $callback);
                }
            }
        }
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }


    public function findNodePath(NodeIdentifier $nodeIdentifier): NodePath
    {
        $cache = $this->inMemoryCache->getNodePathCache();

        if (!$cache->contains($nodeIdentifier)) {
            $result = $this->getDatabaseConnection()->executeQuery(
                '
                -- ContentSubgraph::findNodePath
                with recursive nodePath as (
                SELECT h.name, h.parentnodeanchor FROM neos_contentgraph_node n
                     INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                     AND h.contentstreamidentifier = :contentStreamIdentifier
                     AND h.dimensionspacepointhash = :dimensionSpacePointHash
                     AND n.nodeidentifier = :nodeIdentifier
 
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
                    'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash(),
                    'nodeIdentifier' => (string)$nodeIdentifier
                ]
            )->fetchAll();

            $nodePath = [];

            foreach ($result as $r) {
                $nodePath[] = $r['name'];
            }

            $nodePath = array_reverse($nodePath);
            $nodePath = new NodePath('/' . implode('/', $nodePath));
            $cache->add($nodeIdentifier, $nodePath);
        }

        return $cache->get($nodeIdentifier);
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'dimensionSpacePoint' => $this->dimensionSpacePoint
        ];
    }

    /**
     * @param array $menuLevelNodeIdentifiers
     * @param int $maximumLevels
     * @param ContentRepository\Context\Parameters\VisibilityConstraints $visibilityConstraints
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @return mixed|void
     */
    public function findSubtrees(
        array $entryNodeAggregateIdentifiers,
        int $maximumLevels,
        NodeTypeConstraints $nodeTypeConstraints
    ): SubtreeInterface {
        $query = new SqlQueryBuilder();
        $query->addToQuery('
-- ContentSubgraph::findSubtrees

-- we build a set of recursive trees, ready to be rendered e.g. in a menu. Because the menu supports starting at multiple nodes, we also support starting at multiple nodes at once.
with recursive tree as (
     -- --------------------------------
     -- INITIAL query: select the root nodes of the tree; as given in $menuLevelNodeIdentifiers
     -- --------------------------------
     select
     	n.*,
     	h.contentstreamidentifier,
     	h.name,
     	h.dimensionspacepoint,

     	-- see https://mariadb.com/kb/en/library/recursive-common-table-expressions-overview/#cast-to-avoid-data-truncation
     	CAST("ROOT" AS CHAR(50)) as parentNodeIdentifier,
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
        h.dimensionspacepoint,

     	p.nodeidentifier as parentNodeIdentifier,
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
            ->parameter('entryNodeAggregateIdentifiers', array_map(function (NodeAggregateIdentifier $nodeAggregateIdentifier) {
                return (string)$nodeAggregateIdentifier;
            }, $entryNodeAggregateIdentifiers), Connection::PARAM_STR_ARRAY)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash())
            ->parameter('maximumLevels', $maximumLevels);

        self::addNodeTypeConstraintsToQuery($query, $nodeTypeConstraints, '###NODE_TYPE_CONSTRAINTS###');

        self::addRestrictionEdgeConstraintsToQuery($query, $this->visibilityConstraints, 'n', 'h', '###VISIBILITY_CONSTRAINTS_INITIAL###');
        self::addRestrictionEdgeConstraintsToQuery($query, $this->visibilityConstraints, 'c', 'h', '###VISIBILITY_CONSTRAINTS_RECURSION###');

        $result = $query->execute($this->getDatabaseConnection())->fetchAll();

        $subtreesByNodeIdentifier = [];
        $subtreesByNodeIdentifier['ROOT'] = new Subtree(0);

        foreach ($result as $nodeData) {
            $node = $this->nodeFactory->mapNodeRowToNode($nodeData);
            if (!isset($subtreesByNodeIdentifier[$nodeData['parentNodeIdentifier']])) {
                throw new \Exception('TODO: must not happen');
            }

            $subtree = new Subtree($nodeData['level'], $node);
            $subtreesByNodeIdentifier[$nodeData['parentNodeIdentifier']]->add($subtree);
            $subtreesByNodeIdentifier[$nodeData['nodeidentifier']] = $subtree;
        }

        return $subtreesByNodeIdentifier['ROOT'];
    }

    public function getInMemoryCache(): InMemoryCache
    {
        return $this->inMemoryCache;
    }
}
