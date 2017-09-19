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
     * @var ContentRepository\ValueObject\ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var ContentRepository\ValueObject\DimensionValueCombination
     */
    protected $dimensionValues;

    /**
     * @var string
     */
    protected $identifier;


    public function __construct(ContentRepository\ValueObject\ContentStreamIdentifier $contentStreamIdentifier, ContentRepository\ValueObject\DimensionValueCombination $dimensionValues)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionValues = $dimensionValues;
        $this->identifier = ContentRepository\Utility\SubgraphUtility::hashIdentityComponents(array_merge($dimensionValues->toArray(), ['contentStreamIdentifier' => $contentStreamIdentifier]));
    }


    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getDimensionValues(): ContentRepository\ValueObject\DimensionValueCombination
    {
        return $this->dimensionValues;
    }

    public function getContentStreamIdentifier(): ContentRepository\ValueObject\ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $nodeIdentifier
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findNodeByIdentifier(ContentRepository\ValueObject\NodeIdentifier $nodeIdentifier, ContentRepository\Service\Context $context = null)
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyedge h ON h.childnodesidentifieringraph = n.identifieringraph
 WHERE n.identifierinsubgraph = :nodeIdentifier
 AND h.subgraphidentifier = :subgraphIdentifier',
            [
                'nodeIdentifier' => $nodeIdentifier,
                'subgraphIdentifier' => $this->identifier
            ]
        )->fetch();

        return $nodeData ? $this->mapRawDataToNode($nodeData, $context) : null;
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $parentIdentifier
     * @param ContentRepository\ValueObject\NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @param ContentRepository\Service\Context|null $context
     * @return array
     */
    public function findNodesByParent(
        ContentRepository\ValueObject\NodeIdentifier $parentIdentifier,
        ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null,
        ContentRepository\Service\Context $context = null
    ): array {
        $query = 'SELECT c.* FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyedge h ON h.parentnodesidentifieringraph = p.identifieringraph
 INNER JOIN neos_contentgraph_node c ON h.childnodesidentifieringraph = c.identifieringraph
 WHERE p.identifierinsubgraph = :parentIdentifier
 AND h.subgraphidentifier = :subgraphIdentifier';
        $parameters = [
            'parentIdentifier' => $parentIdentifier,
            'subgraphIdentifier' => $this->identifier
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

    public function countChildNodes(ContentRepository\ValueObject\NodeIdentifier $parentIdentifier, ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null): int
    {
        $query = 'SELECT COUNT(identifieringraph) FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyedge h ON h.parentnodesidentifieringraph = p.identifieringraph
 INNER JOIN neos_contentgraph_node c ON h.childnodesidentifieringraph = c.identifieringraph
 WHERE p.identifierinsubgraph = :parentIdentifier
 AND h.subgraphidentifier = :subgraphIdentifier';
        $parameters = [
            'parentIdentifier' => $parentIdentifier,
            'subgraphIdentifier' => $this->identifier
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
     * @param ContentRepository\ValueObject\NodeIdentifier $parentIdentifier
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findFirstChild(ContentRepository\ValueObject\NodeIdentifier $parentIdentifier, ContentRepository\Service\Context $context = null)
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT c.* FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyedge h ON h.parentnodesidentifieringraph = p.identifieringraph
 INNER JOIN neos_contentgraph_node c ON h.childnodesidentifieringraph = c.identifieringraph
 WHERE p.identifierinsubgraph = :parentIdentifier
 AND h.subgraphidentifier = :subgraphIdentifier
 ORDER BY h.position',
            [
                'parentIdentifier' => $parentIdentifier,
                'subgraphIdentifier' => $this->identifier
            ]
        )->fetch();

        return $nodeData ? $this->mapRawDataToNode($nodeData, $context) : null;
    }

    /**
     * @param string $path
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findByPath(string $path)
    {
        $edgeNames = explode('/', trim($path, '/'));
        $currentNode = $this->findRootNode();
        foreach ($edgeNames as $edgeName) {
            $currentNode = $this->findNodeByParentAlongPath($currentNode->getIdentifier(), $edgeName);
            if (!$currentNode) {
                return null;
            }
        }

        return $currentNode;
    }

    /**
     * @param ContentRepository\ValueObject\NodeIdentifier $parentIdentifier
     * @param string $edgeName
     * @param ContentRepository\Service\Context|null $context
     * @return ContentRepository\Model\NodeInterface|null
     */
    public function findNodeByParentAlongPath(ContentRepository\ValueObject\NodeIdentifier $parentIdentifier, string $edgeName, ContentRepository\Service\Context $context = null)
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT c.* FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyedge h ON h.parentnodesidentifieringraph = p.identifieringraph
 INNER JOIN neos_contentgraph_node c ON h.childnodesidentifieringraph = c.identifieringraph
 WHERE p.identifierinsubgraph = :parentIdentifier
 AND h.subgraphidentifier = :subgraphIdentifier
 AND h.name = :edgeName
 ORDER BY h.position',
            [
                'parentIdentifier' => $parentIdentifier,
                'subgraphIdentifier' => $this->identifier,
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
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT n.*, h.name, h.index FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyedge h ON h.childnodesidentifieringraph = n.identifieringraph
 WHERE n.nodetypename = :nodeTypeName
 AND h.subgraphidentifier = :subgraphIdentifier
 ORDER BY h.position',
            [
                'nodeTypeName' => $nodeTypeName,
                'subgraphIdentifier' => $this->identifier
            ]
        )->fetchAll() as $nodeData) {
            $result[] = $this->mapRawDataToNode($nodeData, $context);
        }

        return $result;
    }

    public function findRootNode(): ContentRepository\Model\NodeInterface
    {
        // TODO: Implement findRootNode() method.
    }


    public function traverse(
        ContentRepository\Model\NodeInterface $parent,
        ContentRepository\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null,
        callable $callback,
        ContentRepository\Service\Context $context = null
    ) {
        $callback($parent);
        foreach ($this->findNodesByParent($parent->getIdentifier()) as $childNode) {
            $this->traverse($childNode, $nodeTypeConstraints, $callback, $context);
        }
    }

    protected function mapRawDataToNode(array $nodeData, ContentRepository\Service\Context $context): ContentRepository\Model\NodeInterface
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeData['nodetypename']);
        $className = $nodeType->getNodeInterfaceImplementationClassName();

        $node = new $className(null, $context);
        $node->nodeType = $nodeType;
        $node->identifier = new ContentRepository\ValueObject\NodeIdentifier($nodeData['identifierinsubgraph']);
        $node->properties = new ContentProjection\PropertyCollection(json_decode($nodeData['properties'], true));
        $node->name = $nodeData['name'];
        $node->index = $nodeData['index'];
        #@todo fetch workspace from finder using the content stream identifier
        #$node->workspace = $this->workspaceRepository->findByIdentifier($this->contentStreamIdentifier);
        $node->subgraphIdentifier = $nodeData['subgraphidentifier'];

        return $node;
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }
}
