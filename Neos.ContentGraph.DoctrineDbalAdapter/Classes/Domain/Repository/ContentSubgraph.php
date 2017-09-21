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
     * @var ContentRepository\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

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
     * @var ContentRepository\Repository\NodeDataRepository
     * @todo get rid of this
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var ContentProjection\ContentGraphInterface
     * @todo get rid of this
     */
    protected $contentGraph;

    /**
     * @var ContentRepository\ValueObject\SubgraphIdentifier
     */
    protected $subgraphIdentifier;


    public function __construct(ContentRepository\ValueObject\SubgraphIdentifier $subgraphIdentifier)
    {
        $this->subgraphIdentifier = $subgraphIdentifier;
    }


    public function getIdentifier(): ContentRepository\ValueObject\SubgraphIdentifier
    {
        return $this->identifier;
    }


    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $nodeIdentifier
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findNodeByIdentifier(ContentRepository\ValueObject\NodeIdentifier $nodeIdentifier, ContentRepository\Service\Context $context = null)
    {

        // NOTE: we join on the relation to ensure that the to-be-fetched identifier is part of the Subgraph
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodesidentifieringraph = n.identifieringraph
 WHERE n.nodeidentifier = :nodeIdentifier
 AND h.subgraphidentityhash = :subgraphIdentityHash',
            [
                'nodeIdentifier' => $nodeIdentifier,
                'subgraphIdentityHash' => $this->subgraphIdentifier->getHash()
            ]
        )->fetch();

        return $nodeData ? $this->mapRawDataToNode($nodeData, $context) : null;
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
 AND h.subgraphidentityhash = :subgraphIdentityHash';
        $parameters = [
            'parentNodeIdentifier' => $parentNodeIdentifier,
            'subgraphIdentityHash' => $this->subgraphIdentifier->getHash()
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
            $result[] = $this->mapRawDataToNode($nodeData, $context);
        }

        return $result;
    }

    public function countChildNodes(ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier, ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null, ContentRepository\Service\Context $contentContext = null): int
    {
        $query = 'SELECT COUNT(c.nodeidentifier) FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeidentifier = p.nodeidentifier
 INNER JOIN neos_contentgraph_node c ON h.childnodeidentifier = c.nodeidentifier
 WHERE p.nodeidentifier = :parentNodeIdentifier
 AND h.subgraphidentityhash = :subgraphIdentityHash';
        $parameters = [
            'parentNodeIdentifier' => $parentNodeIdentifier,
            'subgraphIdentityHash' => $this->subgraphIdentifier->getHash()
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
    public function findParentNode(ContentRepository\ValueObject\NodeIdentifier $childNodeIdentifier, ContentRepository\Service\Context $context = null)
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT p.* FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeidentifier = p.nodeidentifier
 INNER JOIN neos_contentgraph_node c ON h.childnodeidentifier = c.nodeidentifier
 WHERE c.nodeidentifier = :childNodeIdentifier
 AND h.subgraphidentityhash = :subgraphIdentityHash',
            [
                'childNodeIdentifier' => $childNodeIdentifier,
                'subgraphIdentityHash' => $this->subgraphIdentifier->getHash()
            ]
        )->fetch();

        return $nodeData ? $this->mapRawDataToNode($nodeData, $context) : null;
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findFirstChildNode(ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier, ContentRepository\Service\Context $context = null)
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT c.* FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeidentifier = p.nodeidentifier
 INNER JOIN neos_contentgraph_node c ON h.childnodeidentifier = c.nodeidentifier
 WHERE p.nodeidentifier = :parentNodeIdentifier
 AND h.subgraphidentityhash = :subgraphIdentityHash
 ORDER BY h.position LIMIT 1',
            [
                'parentNodeIdentifier' => $parentNodeIdentifier,
                'subgraphIdentityHash' => $this->subgraphIdentifier->getHash()
            ]
        )->fetch();

        return $nodeData ? $this->mapRawDataToNode($nodeData, $context) : null;
    }

    /**
     * @param string $path
     * @param ContentRepository\Service\Context|null $contentContext
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findNodeByPath(string $path, ContentRepository\Service\Context $contentContext = null)
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
     * @param string $edgeName
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findChildNodeConnectedThroughEdgeName(ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier, string $edgeName, ContentRepository\Service\Context $context = null)
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT c.* FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeidentifier = p.nodeidentifier
 INNER JOIN neos_contentgraph_node c ON h.childnodeidentifier = c.nodeidentifier
 WHERE p.nodeidentifier = :parentNodeIdentifier
 AND h.subgraphidentityhash = :subgraphIdentityHash
 AND h.name = :edgeName
 ORDER BY h.position LIMIT 1',
            [
                'parentNodeIdentifier' => $parentNodeIdentifier,
                'subgraphIdentityHash' => $this->subgraphIdentifier->getHash(),
                'edgeName' => $edgeName
            ]
        )->fetch();

        return $nodeData ? $this->mapRawDataToNode($nodeData, $context) : null;
    }

    /**
     * @param string $nodeTypeName
     * @param ContentRepository\Service\Context|null $context
     * @return array|ContentRepository\Model\NodeInterface[]
     */
    public function findNodesByType(string $nodeTypeName, ContentRepository\Service\Context $context = null): array
    {
        $result = [];

        // "Node Type" is a concept of the Node Aggregate; but we can store the node type denormalized in the Node.
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT n.*, h.name, h.index FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeidentifier = n.nodeidentifier
 WHERE n.nodetypename = :nodeTypeName
 AND h.subgraphidentityhash = :subgraphIdentityHash
 ORDER BY h.position',
            [
                'nodeTypeName' => $nodeTypeName,
                'subgraphIdentityHash' => $this->subgraphIdentifier->getHash()
            ]
        )->fetchAll() as $nodeData) {
            $result[] = $this->mapRawDataToNode($nodeData, $context);
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
        return $this->mapRawDataToNode($nodeData, $context);
    }


    public function traverse(
        ContentRepository\Model\NodeInterface $parent,
        ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null,
        callable $callback,
        ContentRepository\Service\Context $context = null
    ) {
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

    protected function mapRawDataToNode(array $nodeDataArrayFromProjection, ContentRepository\Service\Context $context): ContentRepository\Model\NodeInterface
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeDataArrayFromProjection['nodetypename']);
        $className = $nodeType->getNodeInterfaceImplementationClassName();

        $serializedSubgraphIdentifier = json_decode($nodeDataArrayFromProjection['subgraphidentifier'], true);

        // $serializedSubgraphIdentifier is empty for the root node
        if (!empty($serializedSubgraphIdentifier)) {
            $subgraphIdentifier = new ContentRepository\ValueObject\SubgraphIdentifier(
                new ContentRepository\ValueObject\ContentStreamIdentifier($serializedSubgraphIdentifier['contentStreamIdentifier']),
                new ContentRepository\ValueObject\DimensionSpacePoint($serializedSubgraphIdentifier['dimensionSpacePoint'])
            );
            $legacyDimensionValues = $subgraphIdentifier->getDimensionSpacePoint()->toLegacyDimensionArray();
            $query = $this->nodeDataRepository->createQuery();
            $nodeData = $query->matching(
                $query->logicalAnd([
                    $query->equals('workspace', (string) $subgraphIdentifier->getContentStreamIdentifier()),
                    $query->equals('identifier', $nodeDataArrayFromProjection['aggregateidentifier']),
                    $query->equals('dimensionsHash', Utility::sortDimensionValueArrayAndReturnDimensionsHash($legacyDimensionValues))
                ])
            );

            $node = new $className($nodeData, $context);
            $node->nodeType = $nodeType;
            $node->aggregateIdentifier = new ContentRepository\ValueObject\NodeAggregateIdentifier($nodeDataArrayFromProjection['nodeaggregateidentifier']);
            $node->identifier = new ContentRepository\ValueObject\NodeIdentifier($nodeDataArrayFromProjection['nodeidentifier']);
            $node->properties = new ContentProjection\PropertyCollection(json_decode($nodeDataArrayFromProjection['properties'], true));
            $node->name = $nodeDataArrayFromProjection['name'];
            $node->index = $nodeDataArrayFromProjection['index'];
            #@todo fetch workspace from finder using the content stream identifier
            #$node->workspace = $this->workspaceRepository->findByIdentifier($this->contentStreamIdentifier);
            $node->subgraphIdentifier = $subgraphIdentifier;

            return $node;
        } else {
            // root node
            $subgraphIdentifier = null;
            $nodeData = $context->getWorkspace()->getRootNodeData();

            $node = new $className($nodeData, $context);
            $node->nodeType = $nodeType;
            $node->identifier = new ContentRepository\ValueObject\NodeIdentifier($nodeDataArrayFromProjection['nodeidentifier']);
            #@todo fetch workspace from finder using the content stream identifier
            #$node->workspace = $this->workspaceRepository->findByIdentifier($this->contentStreamIdentifier);
            $node->subgraphIdentifier = $subgraphIdentifier;

            return $node;
        }




    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }
}
