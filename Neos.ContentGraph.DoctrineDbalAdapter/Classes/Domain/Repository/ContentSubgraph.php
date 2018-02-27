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
use Neos\ContentGraph\DoctrineDbalAdapter\Infrastructure\Service\DbalClient;
use Neos\ContentRepository\Domain as ContentRepository;
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
     * Runtime cache, to be extended to a fully fledged graph
     * @var array
     */
    protected $inMemorySubgraph;

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
        if (!isset($this->inMemorySubgraph[(string) $nodeIdentifier])) {
            $nodeRow = $this->getDatabaseConnection()->executeQuery(
                'SELECT n.* FROM neos_contentgraph_node n
    WHERE n.nodeidentifier = :nodeIdentifier',
                [
                    'nodeIdentifier' => $nodeIdentifier
                ]
            )->fetch();
            if (!$nodeRow) {
                return null;
            }

            // We always allow root nodes
            if (empty($nodeRow['dimensionspacepointhash'])) {
                $this->inMemorySubgraph[(string) $nodeIdentifier] = $this->nodeFactory->mapNodeRowToNode($nodeRow, $context);
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
                    $this->inMemorySubgraph[(string) $nodeIdentifier] = $this->nodeFactory->mapNodeRowToNode($nodeRow, $context);
                } else {
                    $this->inMemorySubgraph[(string) $nodeIdentifier] = null;
                }
            }
        }
        return $this->inMemorySubgraph[(string) $nodeIdentifier];
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
    ): array {
        $query = 'SELECT c.*, h.name, h.contentstreamidentifier FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeidentifier = :parentNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash';
        $parameters = [
            'parentNodeIdentifier' => $parentNodeIdentifier,
            'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
            'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash()
        ];
        $types = [];

        if ($nodeTypeConstraints) {
            if (!empty ($nodeTypeConstraints->getExplicitlyAllowedNodeTypeNames())) {
                $allowanceQueryPart = 'c.nodetypename IN (:explicitlyAllowedNodeTypeNames)';
                $parameters['explicitlyAllowedNodeTypeNames'] = $nodeTypeConstraints->getExplicitlyAllowedNodeTypeNames();
                $types['explicitlyAllowedNodeTypeNames'] = Connection::PARAM_STR_ARRAY;
            } else {
                $allowanceQueryPart = '';
            }
            if (!empty ($nodeTypeConstraints->getExplicitlyDisallowedNodeTypeNames())) {
                $disAllowanceQueryPart = 'c.nodetypename NOT IN (:explicitlyDisallowedNodeTypeNames)';
                $parameters['explicitlyDisallowedNodeTypeNames'] = $nodeTypeConstraints->getExplicitlyDisallowedNodeTypeNames();
                $types['explicitlyDisallowedNodeTypeNames'] = Connection::PARAM_STR_ARRAY;
            } else {
                $disAllowanceQueryPart = '';
            }
            if ($allowanceQueryPart && $disAllowanceQueryPart) {
                $query .= ' AND (' . $allowanceQueryPart . ($nodeTypeConstraints->isWildcardAllowed() ? ' OR ' : ' AND ') . $disAllowanceQueryPart . ')';
            } elseif ($allowanceQueryPart && !$nodeTypeConstraints->isWildcardAllowed()) {
                $query .= ' AND ' . $allowanceQueryPart;
            } elseif ($disAllowanceQueryPart) {
                $query .= ' AND ' . $disAllowanceQueryPart;
            }
        }
        $query .= '
 ORDER BY h.position DESC';

        $result = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            $query,
            $parameters,
            $types
        )->fetchAll() as $nodeData) {
            $result[] = $this->nodeFactory->mapNodeRowToNode($nodeData, $context);
        }

        return $result;
    }

    public function findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier, ContentRepository\Service\Context $context = null): ?ContentRepository\Model\NodeInterface
    {
        $query = 'SELECT n.*, h.name, h.contentstreamidentifier FROM neos_contentgraph_node n
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
        $query = 'SELECT COUNT(c.nodeidentifier) FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE p.nodeidentifier = :parentNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash';
        $parameters = [
            'parentNodeIdentifier' => $parentNodeIdentifier,
            'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
            'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash()
        ];

        if ($nodeTypeConstraints) {
            // @todo apply constraints
            throw new \Exception('TODO: Constraints not supported');
        }

        return $this->getDatabaseConnection()->executeQuery(
            $query,
            $parameters
        )->rowCount();
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $childNodeIdentifier
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findParentNode(ContentRepository\ValueObject\NodeIdentifier $childNodeIdentifier, ContentRepository\Service\Context $context = null): ?ContentRepository\Model\NodeInterface
    {
        $params = [
            'childNodeIdentifier' => (string)$childNodeIdentifier,
            'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
            'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash()
        ];
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT p.*, h.contentstreamidentifier, hp.name FROM neos_contentgraph_node p
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

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode($nodeRow, $context) : null;
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findFirstChildNode(ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier, ContentRepository\Service\Context $context = null): ?ContentRepository\Model\NodeInterface
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT c.* FROM neos_contentgraph_node p
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
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT c.*, h.name, h.contentstreamidentifier FROM neos_contentgraph_node p
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


        return $nodeData ? $this->nodeFactory->mapNodeRowToNode($nodeData, $context) : null;
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
            'SELECT n.*, h.name, h.position FROM neos_contentgraph_node n
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
                $parentNode = $this->findParentNode($startNode->getNodeIdentifier());
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
        $result = $this->getDatabaseConnection()->executeQuery(
            'with recursive nodePath as (
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
                'nodeIdentifier' => (string) $nodeIdentifier
            ]
        )->fetchAll();

        $nodePath = [];

        foreach ($result as $r) {
            $nodePath[] = $r['name'];
        }

        $nodePath = array_reverse($nodePath);
        return new ContentRepository\ValueObject\NodePath('/' . implode('/', $nodePath));
    }

    public function resetCache()
    {
        $this->inMemorySubgraph = [];
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'dimensionSpacePoint' => $this->dimensionSpacePoint
        ];
    }


}
