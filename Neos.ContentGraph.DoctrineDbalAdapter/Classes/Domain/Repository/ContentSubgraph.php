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
use Neos\ContentRepository\Domain\Projection\Content\InMemoryCache;
use Neos\ContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\Context\Node\SubtreeInterface;
use Neos\ContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeConstraints;
use Neos\ContentRepository\Utility;
use Neos\Flow\Annotations as Flow;

/**
 * The content subgraph application repository
 *
 * To be used as a read-only source of nodes.
 *
 *
 * ## Backwards Compatibility
 *
 * The *Context* argument in all methods is only used to create legacy Node instances; so it should NEVER be used except inside mapNodeRowToNode.
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
     * @var ContentRepository\Service\NodeTypeConstraintService
     */
    protected $nodeTypeConstraintService;

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
     * @var ContentRepository\ValueObject\ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var ContentRepository\ValueObject\DimensionSpacePoint
     */
    protected $dimensionSpacePoint;


    public function __construct(ContentRepository\ValueObject\ContentStreamIdentifier $contentStreamIdentifier, ContentRepository\ValueObject\DimensionSpacePoint $dimensionSpacePoint)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->inMemoryCache = new InMemoryCache();
    }

    /**
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param $query
     */
    protected static function addNodeTypeConstraintsToQuery(ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null, SqlQueryBuilder $query, string $markerToReplaceInQuery = null): void
    {
        if ($nodeTypeConstraints) {
            if (!empty ($nodeTypeConstraints->getExplicitlyAllowedNodeTypeNames())) {
                $allowanceQueryPart = 'c.nodetypename IN (:explicitlyAllowedNodeTypeNames)';
                $query->parameter('explicitlyAllowedNodeTypeNames', $nodeTypeConstraints->getExplicitlyAllowedNodeTypeNames(), Connection::PARAM_STR_ARRAY);
            } else {
                $allowanceQueryPart = '';
            }
            if (!empty ($nodeTypeConstraints->getExplicitlyDisallowedNodeTypeNames())) {
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
            }
        }
    }


    public function getContentStreamIdentifier(): ContentRepository\ValueObject\ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getDimensionSpacePoint(): ContentRepository\ValueObject\DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $nodeIdentifier
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     * @throws \Neos\ContentRepository\Exception\NodeConfigurationException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function findNodeByIdentifier(ContentRepository\ValueObject\NodeIdentifier $nodeIdentifier, ContentRepository\Service\Context $context = null): ?ContentRepository\Model\NodeInterface
    {
        $cache = $this->inMemoryCache->getNodeByNodeIdentifierCache();
        if ($cache->knowsAbout($nodeIdentifier)) {
            return $cache->get($nodeIdentifier);
        } else {
            $nodeRow = $this->getDatabaseConnection()->executeQuery(
                'SELECT n.* FROM neos_contentgraph_node n
    WHERE n.nodeidentifier = :nodeIdentifier',
                [
                    'nodeIdentifier' => $nodeIdentifier
                ]
            )->fetch();
            if (!$nodeRow) {
                $cache->rememberNonExistingNodeIdentifier($nodeIdentifier);
                return null;
            }

            // We always allow root nodes
            if (empty($nodeRow['dimensionspacepointhash'])) {
                $node = $this->nodeFactory->mapNodeRowToNode($nodeRow, $context);
                $cache->add($nodeIdentifier, $node);
                return $node;
            } else {
                // We are NOT allowed at this point to access the $nodeRow above anymore; as we only fetched an *arbitrary* node with the identifier; but
                // NOT the correct one taking content stream and dimension space point into account. In the query below, we fetch everything we need.

                $nodeRow = $this->getDatabaseConnection()->executeQuery(
                    'SELECT n.*, h.name, h.contentstreamidentifier, h.dimensionspacepoint FROM neos_contentgraph_node n
     INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
     WHERE n.nodeidentifier = :nodeIdentifier
     AND h.contentstreamidentifier = :contentStreamIdentifier       
     AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                    [
                        'nodeIdentifier' => (string)$nodeIdentifier,
                        'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
                        'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash()
                    ]
                )->fetch();

                if (is_array($nodeRow)) {
                    $node = $this->nodeFactory->mapNodeRowToNode($nodeRow, $context);
                    $cache->add($nodeIdentifier, $node);
                    return $node;
                } else {
                    $cache->rememberNonExistingNodeIdentifier($nodeIdentifier);
                    return null;
                }
            }
        }

    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier
     * @param ContentRepository\ValueObject\NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @param ContentRepository\Service\Context|null $context
     * @return array|ContentRepository\Model\NodeInterface[]
     * @throws \Exception
     */
    public function findChildNodes(
        ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier,
        ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null,
        ContentRepository\Service\Context $context = null
    ): array
    {
        $cache = $this->inMemoryCache->getAllChildNodesByNodeIdentifierCache();
        $namedChildNodeCache = $this->inMemoryCache->getNamedChildNodeByNodeIdentifierCache();
        $parentNodeIdentifierCache = $this->inMemoryCache->getParentNodeIdentifierByChildNodeIdentifierCache();

        if ($cache->contains($parentNodeIdentifier)) {
            return $cache->findChildNodes($parentNodeIdentifier, $nodeTypeConstraints, $limit, $offset);
        }
        $query = new SqlQueryBuilder();
        $query->addToQuery('
-- ContentSubgraph::findChildNodes
SELECT c.*, h.name, h.contentstreamidentifier FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeidentifier = :parentNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash')
            ->parameter('parentNodeIdentifier', $parentNodeIdentifier)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash());

        self::addNodeTypeConstraintsToQuery($nodeTypeConstraints, $query);
        $query->addToQuery('ORDER BY h.position DESC');

        $result = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAll() as $nodeData) {
            $node = $this->nodeFactory->mapNodeRowToNode($nodeData, $context);
            $result[] = $node;
            $namedChildNodeCache->add($parentNodeIdentifier, $node->getNodeName(), $node);
            $parentNodeIdentifierCache->add($node->getNodeIdentifier(), $parentNodeIdentifier);
        }

        if ($nodeTypeConstraints === null && $limit === null && $offset === null) {
            $cache->add($parentNodeIdentifier, $result);
        }

        return $result;
    }

    public function findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier, ContentRepository\Service\Context $context = null): ?ContentRepository\Model\NodeInterface
    {
        $query = '
-- ContentSubgraph::findNodeByNodeAggregateIdentifier
SELECT n.*, h.name, h.contentstreamidentifier FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash';
        $parameters = [
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
            'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash()
        ];
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            $query,
            $parameters
        )->fetch();
        if ($nodeRow === false) {
            return null;
        }
        return $this->nodeFactory->mapNodeRowToNode($nodeRow, $context);
    }

    public function countChildNodes(
        ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier,
        ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null,
        ContentRepository\Service\Context $contentContext = null
    ): int
    {
        $query = new SqlQueryBuilder();
        $query->addToQuery('SELECT COUNT(c.nodeidentifier) FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeidentifier = :parentNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash')
            ->parameter('parentNodeIdentifier', $parentNodeIdentifier)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash());

        if ($nodeTypeConstraints) {
            self::addNodeTypeConstraintsToQuery($nodeTypeConstraints, $query);
        }

        return $query->execute($this->getDatabaseConnection())->rowCount();
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $nodeIdentifier
     * @param ContentRepository\ValueObject\PropertyName $nodeTypeConstraints
     * @param ContentRepository\Service\Context|null $contentContext
     * @return ContentRepository\Model\NodeInterface[]
     */
    public function findReferencedNodes(ContentRepository\ValueObject\NodeIdentifier $nodeIdentifier, ContentRepository\ValueObject\PropertyName $name = null, ContentRepository\Service\Context $context = null) :array
    {
        $params = [
            'nodeIdentifier' => (string)$nodeIdentifier,
            'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
            'dimensionSpacePointHash' => (string)$this->getDimensionSpacePoint()->getHash(),
            'name' => (string)$name
        ];

        $query = 'SELECT d.*, dh.contentstreamidentifier, dh.name FROM neos_contentgraph_hierarchyrelation sh
 INNER JOIN neos_contentgraph_node s ON sh.childnodeanchor = s.relationanchorpoint 
 INNER JOIN neos_contentgraph_referencerelation r ON s.relationanchorpoint = r.nodeanchorpoint
 INNER JOIN neos_contentgraph_node d ON r.destinationnodeaggregateidentifier = d.nodeaggregateidentifier
 INNER JOIN neos_contentgraph_hierarchyrelation dh ON dh.childnodeanchor = d.relationanchorpoint  
 WHERE s.nodeidentifier = :nodeIdentifier
 AND dh.dimensionspacepointhash = :dimensionSpacePointHash
 AND sh.dimensionspacepointhash = :dimensionSpacePointHash
 AND dh.contentstreamidentifier = :contentStreamIdentifier
 AND sh.contentstreamidentifier = :contentStreamIdentifier
';

        if ($name) {
            $query .= '
 AND r.name = :name
 ORDER BY r.position';
        } else {
            $query .= '
 ORDER BY r.name, r.position';
        }

        $result = [];
        foreach ($this->getDatabaseConnection()->executeQuery($query, $params)->fetchAll() as $nodeData) {
            $result[] = $this->nodeFactory->mapNodeRowToNode($nodeData, $context);
        }
        return $result;
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $childNodeIdentifier
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findParentNode(ContentRepository\ValueObject\NodeIdentifier $childNodeIdentifier, ContentRepository\Service\Context $context = null): ?ContentRepository\Model\NodeInterface
    {
        $cache = $this->inMemoryCache->getParentNodeIdentifierByChildNodeIdentifierCache();

        $possibleParentIdentifier = $cache->get($childNodeIdentifier);
        if ($possibleParentIdentifier) {
            // we here trigger findNodeByIdentifier, as this might retrieve the Parent Node from the in-memory cache.
            return $this->findNodeByIdentifier($possibleParentIdentifier, $context);
        }

        $params = [
            'childNodeIdentifier' => (string)$childNodeIdentifier,
            'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
            'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash()
        ];
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            '
-- ContentSubgraph::findParentNode
SELECT p.*, h.contentstreamidentifier, hp.name FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 INNER JOIN neos_contentgraph_hierarchyrelation hp ON hp.childnodeanchor = p.relationanchorpoint
 WHERE c.nodeidentifier = :childNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND hp.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 AND hp.dimensionspacepointhash = :dimensionSpacePointHash',
            $params
        )->fetch();

        $node = $nodeRow ? $this->nodeFactory->mapNodeRowToNode($nodeRow, $context) : null;
        if ($node) {
            $cache->add($childNodeIdentifier, $node->getNodeIdentifier());

            // we also add the parent node to the NodeIdentifier => Node cache; as this might improve cache hit rates as well.
            $this->inMemoryCache->getNodeByNodeIdentifierCache()->add($node->getNodeIdentifier(), $node);
        }
        return $node;
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findFirstChildNode(ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier, ContentRepository\Service\Context $context = null): ?ContentRepository\Model\NodeInterface
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            '
-- ContentSubgraph::findFirstChildNode
SELECT c.* FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeidentifier = :parentNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 ORDER BY h.position LIMIT 1',
            [
                'parentNodeIdentifier' => $parentNodeIdentifier,
                'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
                'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash()
            ]
        )->fetch();

        return $nodeData ? $this->nodeFactory->mapNodeRowToNode($nodeData, $context) : null;
    }

    /**
     * @param string $path
     * @param NodeIdentifier $startingNodeIdentifier
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findNodeByPath(string $path, NodeIdentifier $startingNodeIdentifier, ContentRepository\Service\Context $context = null): ?ContentRepository\Model\NodeInterface
    {
        $currentNode = $this->findNodeByIdentifier($startingNodeIdentifier, $context);
        if (!$currentNode) {
            throw new \RuntimeException('Starting Node (identified by ' . $startingNodeIdentifier . ') does not exist.');
        }
        $edgeNames = explode('/', trim($path, '/'));
        if ($edgeNames !== [""]) {
            foreach ($edgeNames as $edgeName) {
                // identifier exists here :)
                $currentNode = $this->findChildNodeConnectedThroughEdgeName($currentNode->getNodeIdentifier(),
                    new NodeName($edgeName), $context);
                if (!$currentNode) {
                    return null;
                }
            }
        }

        return $currentNode;
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier
     * @param ContentRepository\ValueObject\NodeName $edgeName
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findChildNodeConnectedThroughEdgeName(
        ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier,
        ContentRepository\ValueObject\NodeName $edgeName,
        ContentRepository\Service\Context $context = null
    ): ?ContentRepository\Model\NodeInterface
    {
        $cache = $this->inMemoryCache->getNamedChildNodeByNodeIdentifierCache();
        if (!$cache->contains($parentNodeIdentifier, $edgeName)) {
            $nodeData = $this->getDatabaseConnection()->executeQuery(
                '
-- ContentGraph::findChildNodeConnectedThroughEdgeName
SELECT c.*, h.name, h.contentstreamidentifier FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeidentifier = :parentNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 AND h.name = :edgeName
 ORDER BY h.position LIMIT 1',
                [
                    'parentNodeIdentifier' => (string)$parentNodeIdentifier,
                    'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
                    'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash(),
                    'edgeName' => (string)$edgeName
                ]
            )->fetch();


            if ($nodeData) {
                $node = $this->nodeFactory->mapNodeRowToNode($nodeData, $context);
                if ($node) {
                    $cache->add($parentNodeIdentifier, $edgeName, $node);
                }
            }
        }
        return $cache->get($parentNodeIdentifier, $edgeName);
    }

    /**
     * @param ContentRepository\ValueObject\NodeTypeName $nodeTypeName
     * @param ContentRepository\Service\Context|null $context
     * @return array|ContentRepository\Model\NodeInterface[]
     */
    public function findNodesByType(ContentRepository\ValueObject\NodeTypeName $nodeTypeName, ContentRepository\Service\Context $context = null): array
    {
        $result = [];

        // "Node Type" is a concept of the Node Aggregate; but we can store the node type denormalized in the Node.
        foreach ($this->getDatabaseConnection()->executeQuery(
            '
-- ContentSubgraph::findNodesByType
SELECT n.*, h.name, h.position FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = n.relationanchorpoint
 WHERE n.nodetypename = :nodeTypeName
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 ORDER BY h.position',
            [
                'nodeTypeName' => $nodeTypeName,
                'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
                'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash(),
            ]
        )->fetchAll() as $nodeData) {
            $result[] = $this->nodeFactory->mapNodeRowToNode($nodeData, $context);
        }

        return $result;
    }

    /**
     * @param ContentRepository\Model\NodeInterface $startNode
     * @param ContentProjection\HierarchyTraversalDirection $direction
     * @param ContentRepository\ValueObject\NodeTypeConstraints|null $nodeTypeConstraints
     * @param callable $callback
     * @param ContentRepository\Service\Context|null $context
     * @throws \Exception
     */
    public function traverseHierarchy(
        ContentRepository\Model\NodeInterface $startNode,
        ContentProjection\HierarchyTraversalDirection $direction = null,
        ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null,
        callable $callback,
        ContentRepository\Service\Context $context = null
    ): void
    {
        if (is_null($direction)) {
            $direction = ContentProjection\HierarchyTraversalDirection::down();
        }

        $continueTraversal = $callback($startNode);
        if ($continueTraversal) {
            if ($direction->isUp()) {
                $parentNode = $this->findParentNode($startNode->getNodeIdentifier(), $context);
                if ($parentNode) {
                    $this->traverseHierarchy($parentNode, $direction, $nodeTypeConstraints, $callback, $context);
                }
            } elseif ($direction->isDown()) {
                foreach ($this->findChildNodes(
                    $startNode->getNodeIdentifier(),
                    $nodeTypeConstraints,
                    null,
                    null,
                    $context
                ) as $childNode) {
                    $this->traverseHierarchy($childNode, $direction, $nodeTypeConstraints, $callback, $context);
                }
            }
        }
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }


    public function findNodePath(ContentRepository\ValueObject\NodeIdentifier $nodeIdentifier): ContentRepository\ValueObject\NodePath
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
            $nodePath = new ContentRepository\ValueObject\NodePath('/' . implode('/', $nodePath));
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
     * @param ContentRepository\Context\Parameters\ContextParameters $contextParameters
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param ContentRepository\Service\Context|null $context
     * @return mixed|void
     */
    public function findSubtrees(array $entryNodeIdentifiers, int $maximumLevels, ContentRepository\Context\Parameters\ContextParameters $contextParameters, NodeTypeConstraints $nodeTypeConstraints, ContentRepository\Service\Context $context = null): SubtreeInterface
    {
        // TODO: evaluate ContextParameters

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
        n.nodeidentifier in (:entryNodeIdentifiers)
        and n.hidden = false             -- TODO - add ContextParameters query part
        and h.contentstreamidentifier = :contentStreamIdentifier
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
union
     -- --------------------------------
     -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
     -- --------------------------------	
     select
        c.*,
        h.contentstreamidentifier,
        h.name,
        
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
		and p.level + 1 <= :maximumLevels -- MAXIMUM LEVELS -- TODO - off by one errors?
	    and c.hidden = false -- TODO - add ContextParameters query part
        ###NODE_TYPE_CONSTRAINTS###
 
   -- select relationanchorpoint from neos_contentgraph_node
) 
select * from tree
order by level, position desc;')
            ->parameter('entryNodeIdentifiers', array_map(function (NodeIdentifier $nodeIdentifier) {
                return (string)$nodeIdentifier;
            }, $entryNodeIdentifiers), Connection::PARAM_STR_ARRAY)
            ->parameter('contentStreamIdentifier', (string)$this->getContentStreamIdentifier())
            ->parameter('dimensionSpacePointHash', $this->getDimensionSpacePoint()->getHash())
            ->parameter('maximumLevels', $maximumLevels);

        self::addNodeTypeConstraintsToQuery($nodeTypeConstraints, $query, '###NODE_TYPE_CONSTRAINTS###');

        $result = $query->execute($this->getDatabaseConnection())->fetchAll();

        $subtreesByNodeIdentifier = [];
        $subtreesByNodeIdentifier['ROOT'] = new Subtree(0);

        foreach ($result as $nodeData) {
            $node = $this->nodeFactory->mapNodeRowToNode($nodeData, $context);
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
