<?php
namespace Neos\ContentRepository\TypeConverter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Error;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\Exception\TypeConverterException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context as ContentRepositoryContext;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeServiceInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\ContentRepository\Exception\NodeException;

/**
 * An Object Converter for Nodes which can be used for routing (but also for other
 * purposes) as a plugin for the Property Mapper.
 *
 * @Flow\Scope("singleton")
 */
class NodeConverter extends AbstractTypeConverter
{
    use NodeLikeConverterHelperTrait;

    /**
     * @var boolean
     */
    const REMOVED_CONTENT_SHOWN = 1;

    /**
     * @var array
     */
    protected $sourceTypes = array('string', 'array');

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeServiceInterface
     */
    protected $nodeService;

    /**
     * @var string
     */
    protected $targetType = NodeInterface::class;

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Converts the specified $source into a Node.
     *
     * If $source is a UUID it is expected to refer to the identifier of a NodeData record of the "live" workspace
     *
     * Otherwise $source has to be a valid node path:
     *
     * The node path must be an absolute context node path and can be specified as a string or as an array item with the
     * key "__contextNodePath". The latter case is for updating existing nodes.
     *
     * This conversion method does not support / allow creation of new nodes because new nodes should be created through
     * the createNode() method of an existing reference node.
     *
     * Also note that the context's "current node" is not affected by this object converter, you will need to set it to
     * whatever node your "current" node is, if any.
     *
     * All elements in the source array which start with two underscores (like __contextNodePath) are specially treated
     * by this converter.
     *
     * All elements in the source array which start with a *single underscore (like _hidden) are *directly* set on the Node
     * object.
     *
     * All other elements, not being prefixed with underscore, are properties of the node.
     *
     * @param string|array $source Either a string or array containing the absolute context node path which identifies the node. For example "/sites/mysitecom/homepage/about@user-admin"
     * @param string $targetType not used
     * @param array $subProperties not used
     * @param PropertyMappingConfigurationInterface $configuration
     * @return mixed An object or \Neos\Error\Messages\Error if the input format is not supported or could not be converted for other reasons
     * @throws NodeException
     */
    public function convertFrom($source, $targetType = null, array $subProperties = array(), PropertyMappingConfigurationInterface $configuration = null)
    {
        if (is_string($source)) {
            try {
                $source = $this->prepareStringSource($source);
            } catch (\InvalidArgumentException $exception) {
                return new Error('Could not convert array to Node object because the node path was invalid.', 1285162903);
            }
        }

        if (isset($source['__contextNodePath'])) {
            try {
                $source = array_merge($source, $this->explodeNodePath($source['__contextNodePath']));
            } catch (\InvalidArgumentException $exception) {
                return new Error('Could not convert array to Node object because the node path was invalid.', 1285162903);
            }
            unset($source['__contextNodePath']);
        }

        if (!isset($source['__nodePath']) && !isset($source['__identifier'])) {
            return new Error('Could not convert ' . gettype($source) . ' to Node object, a valid absolute (context) node path or identifier as a string or an array is expected.', 1302879936);
        }

        $context = $this->contextFactory->create($this->prepareContextProperties($source, $configuration));

        $workspace = $context->getWorkspace(false);
        if (!$workspace) {
            return new Error(sprintf('Could not convert the given source to Node object because the workspace "%s" as specified in the context node path does not exist.', $source['__workspaceName']), 1383577859);
        }

        $node = $this->getNode($context, $source);

        if (!$node) {
            return new Error(sprintf('Could not convert array to Node object because the node "%s" does not exist.', $nodePath), 1370502328);
        }

        $node = $this->changeNodeTypeIfRequested($node, $source);
        unset($source['_nodeType']);

        $this->setNodeProperties($node, $source, $configuration);
        return $node;
    }

    /**
     * Adjusts a string source to an array source.
     *
     * @param $source
     * @return array
     */
    protected function prepareStringSource($source)
    {
        $actualSoure = [
            '__workspaceName' => 'live',
            '__dimensions' => []
        ];
        // Doesn't start with slash so we must assume this is a node identifier
        if (strpos($source, '/') !== 0) {
            $actualSoure['__identifier'] = $source;
            return $actualSoure;
        }

        return $this->explodeNodePath($source);
    }

    /**
     * Explode a (context) node path to an array of source properties.
     *
     * @param $nodePath
     * @return array
     */
    protected function explodeNodePath($nodePath)
    {
        $explodedPath = NodePaths::explodeContextPath($nodePath);
        $actualSoure = [
            '__nodePath' => $explodedPath['nodePath'],
            '__workspaceName' => $explodedPath['workspaceName'],
            '__dimensions' => $explodedPath['dimensions']
        ];

        return $actualSoure;
    }

    /**
     * Get a node from the context with the given source.
     *
     * @param ContentRepositoryContext $context
     * @param array $source
     * @return NodeInterface
     */
    protected function getNode(ContentRepositoryContext $context, array $source)
    {
        if (isset($source['__identifier'])) {
            return $context->getNodeByIdentifier($source['__identifier']);
        }

        return $context->getNode($source['__nodePath']);
    }

    /**
     * Changes node type if requested in source.
     *
     * @param NodeInterface $node
     * @param array $source
     * @return NodeInterface
     * @throws NodeException
     */
    protected function changeNodeTypeIfRequested(NodeInterface $node, array $source)
    {
        if (!isset($source['_nodeType']) || $source['_nodeType'] === $node->getNodeType()->getName()) {
            return $node;
        }

        if ($node->getContext()->getWorkspace()->isPublicWorkspace()) {
            throw new NodeException('Could not convert the node type in public workspace', 1429989736);
        }

        $oldNodeType = $node->getNodeType();
        $targetNodeType = $this->nodeTypeManager->getNodeType($source['_nodeType']);
        $node->setNodeType($targetNodeType);
        $this->nodeService->setDefaultValues($node);
        $this->nodeService->cleanUpAutoCreatedChildNodes($node, $oldNodeType);
        $this->nodeService->createChildNodes($node);

        return $node;
    }

    /**
     * Iterates through the given $properties setting them on the specified $node using the appropriate TypeConverters.
     *
     * @param NodeInterface $node
     * @param array $properties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return void
     * @throws TypeConverterException
     */
    protected function setNodeProperties(NodeInterface $node, array $properties, PropertyMappingConfigurationInterface $configuration = null)
    {
        $this->_setNodeProperties($node, $node->getNodeType(), $properties, $node->getContext(), $this->objectManager, $this->propertyMapper, $configuration);
    }
}
