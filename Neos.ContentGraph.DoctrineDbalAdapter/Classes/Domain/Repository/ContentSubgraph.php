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
use Neos\ContentRepository\Utility;
use Neos\Flow\Annotations as Flow;

/**
 * The content subgraph application repository
 *
 * To be used as a read-only source of nodes
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
     * @var ContentRepository\Repository\WorkspaceRepository
     */
    protected $workspaceRepository;

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
     * @Flow\Inject
     * @var ContentProjection\ContentGraphInterface
     * @todo get rid of this
     */
    protected $contentGraph;

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
     */
    public function findNodeByIdentifier(ContentRepository\ValueObject\NodeIdentifier $nodeIdentifier, ContentRepository\Service\Context $context = null): ?ContentRepository\Model\NodeInterface
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM neos_contentgraph_node n
WHERE n.nodeidentifier = :nodeIdentifier',
            [
                'nodeIdentifier' => $nodeIdentifier
            ]
        )->fetch();
        if (!$nodeData) {
            return null;
        }

        // We always allow root nodes
        if (empty($nodeData['dimensionspacepointhash'])) {
            return $this->nodeFactory->mapRawDataToNode($nodeData, $context);
        }

        $inboundEdgeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
 WHERE h.childnodeidentifier = :nodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier       
 AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'nodeIdentifier' => (string)$nodeIdentifier,
                'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
                'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash()
            ]
        )->fetch();

        // we only allow nodes matching the content stream identifier and dimension space point
        return $inboundEdgeData ? $this->nodeFactory->mapRawDataToNode($nodeData, $context) : null;
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier
     * @param ContentRepository\ValueObject\NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @param ContentRepository\Service\Context|null $context
     * @return array
     */
    public function findChildNodes(
        ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier,
        ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null,
        ContentRepository\Service\Context $context = null
    ): array {
        $query = 'SELECT c.* FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeidentifier = p.nodeidentifier
 INNER JOIN neos_contentgraph_node c ON h.childnodeidentifier = c.nodeidentifier
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
        }
        $query .= '
 ORDER BY h.position';
        $result = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            $query,
            $parameters
        )->fetchAll() as $nodeData) {
            $result[] = $this->nodeFactory->mapRawDataToNode($nodeData, $context);
        }

        return $result;
    }

    public function countChildNodes(
        ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier,
        ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null,
        ContentRepository\Service\Context $contentContext = null
    ): int {
        $query = 'SELECT COUNT(c.nodeidentifier) FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeidentifier = p.nodeidentifier
 INNER JOIN neos_contentgraph_node c ON h.childnodeidentifier = c.nodeidentifier
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
        }

        return $this->getDatabaseConnection()->executeQuery(
            $query,
            $parameters
        )->fetch();
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $childNodeIdentifier
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findParentNode(ContentRepository\ValueObject\NodeIdentifier $childNodeIdentifier, ContentRepository\Service\Context $context = null): ?ContentRepository\Model\NodeInterface
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT p.* FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeidentifier = p.nodeidentifier
 INNER JOIN neos_contentgraph_node c ON h.childnodeidentifier = c.nodeidentifier
 WHERE c.nodeidentifier = :childNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'childNodeIdentifier' => $childNodeIdentifier,
                'contentStreamIdentifier' => (string)$this->getContentStreamIdentifier(),
                'dimensionSpacePointHash' => $this->getDimensionSpacePoint()->getHash()
            ]
        )->fetch();

        return $nodeData ? $this->nodeFactory->mapRawDataToNode($nodeData, $context) : null;
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
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeidentifier = p.nodeidentifier
 INNER JOIN neos_contentgraph_node c ON h.childnodeidentifier = c.nodeidentifier
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

        return $nodeData ? $this->nodeFactory->mapRawDataToNode($nodeData, $context) : null;
    }

    /**
     * @param string $path
     * @param ContentRepository\Service\Context|null $contentContext
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findNodeByPath(string $path, ContentRepository\Service\Context $contentContext = null): ?ContentRepository\Model\NodeInterface
    {
        $edgeNames = explode('/', trim($path, '/'));
        $currentNode = $this->findRootNode();
        foreach ($edgeNames as $edgeName) {
            // identifier exists here :)
            $currentNode = $this->findChildNodeConnectedThroughEdgeName($currentNode->identifier, $edgeName, $contentContext);
            if (!$currentNode) {
                return null;
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
            'SELECT c.* FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeidentifier = p.nodeidentifier
 INNER JOIN neos_contentgraph_node c ON h.childnodeidentifier = c.nodeidentifier
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

        return $nodeData ? $this->nodeFactory->mapRawDataToNode($nodeData, $context) : null;
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
            'SELECT n.*, h.name, h.index FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeidentifier = n.nodeidentifier
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
            $result[] = $this->nodeFactory->mapRawDataToNode($nodeData, $context);
        }

        return $result;
    }

    /**
     * Root Node by definition belongs to every subgraph (it is "colorless"); that's why we do not filter on subgraph here.
     *
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface
     */
    public function findRootNode(ContentRepository\Service\Context $context = null): ContentRepository\Model\NodeInterface
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM neos_contentgraph_node n
 WHERE n.nodetypename = :nodeTypeName',
            [
                'nodeTypeName' => 'Neos.ContentGraph:Root',
            ]
        )->fetch();

        return $this->nodeFactory->mapRawDataToNode($nodeData, $context);
    }


    /**
     * @param ContentRepository\Model\NodeInterface $parent
     * @param ContentRepository\ValueObject\NodeTypeConstraints|null $nodeTypeConstraints
     * @param callable $callback
     * @param ContentRepository\Service\Context|null $context
     */
    public function traverse(
        ContentRepository\Model\NodeInterface $parent,
        ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null,
        callable $callback,
        ContentRepository\Service\Context $context = null
    ): void {
        $callback($parent);
        foreach ($this->findChildNodes(
            $parent->identifier,
            $nodeTypeConstraints,
            null,
            null,
            $context
        ) as $childNode) {
            $this->traverse($childNode, $nodeTypeConstraints, $callback, $context);
        }
    }


    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }
}
