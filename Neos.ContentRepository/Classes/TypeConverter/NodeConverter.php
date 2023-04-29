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
use Neos\Utility\ObjectAccess;
use Neos\Flow\Security\Context;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
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
    /**
     * @var int
     */
    const REMOVED_CONTENT_SHOWN = 1;

    /**
     * @var int
     */
    const INVISIBLE_CONTENT_SHOWN = 2;

    /**
     * @var array
     */
    protected $sourceTypes = ['string', 'array'];

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

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
     * @var NodeFactory
     */
    protected $nodeFactory;

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
    public function convertFrom($source, $targetType, array $subProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if (is_string($source)) {
            $source = ['__contextNodePath' => $source];
        }

        if (!is_array($source) || !isset($source['__contextNodePath'])) {
            return new Error('Could not convert ' . gettype($source) . ' to Node object, a valid absolute context node path as a string or array is expected.', 1302879936);
        }

        try {
            $nodePathAndContext = NodePaths::explodeContextPath($source['__contextNodePath']);
            $nodePath = $nodePathAndContext['nodePath'];
            $workspaceName = $nodePathAndContext['workspaceName'];
            $dimensions = $nodePathAndContext['dimensions'];
        } catch (\InvalidArgumentException $exception) {
            return new Error('Could not convert array to Node object because the node path was invalid.', 1285162903);
        }

        $context = $this->contextFactory->create($this->prepareContextProperties($workspaceName, $configuration, $dimensions));
        $workspace = $context->getWorkspace(false);
        if (!$workspace) {
            return new Error(sprintf('Could not convert the given source to Node object because the workspace "%s" as specified in the context node path does not exist.', $workspaceName), 1383577859);
        }

        $node = $context->getNode($nodePath);
        if (!$node) {
            return new Error(sprintf('Could not convert array to Node object because the node "%s" does not exist.', $nodePath), 1370502328);
        }

        if (isset($source['_nodeType']) && $source['_nodeType'] !== $node->getNodeType()->getName()) {
            if ($context->getWorkspace()->getName() === 'live') {
                throw new NodeException('Could not convert the node type in live workspace', 1429989736);
            }

            $oldNodeType = $node->getNodeType();
            $targetNodeType = $this->nodeTypeManager->getNodeType($source['_nodeType']);
            $node->setNodeType($targetNodeType);
            $this->nodeService->setDefaultValues($node);
            $this->nodeService->cleanUpAutoCreatedChildNodes($node, $oldNodeType);
            $this->nodeService->createChildNodes($node);
        }
        unset($source['_nodeType']);

        $this->setNodeProperties($node, $node->getNodeType(), $source, $context, $configuration);
        return $node;
    }

    /**
     * Iterates through the given $properties setting them on the specified $node using the appropriate TypeConverters.
     *
     * @param object $nodeLike
     * @param NodeType $nodeType
     * @param array $properties
     * @param ContentRepositoryContext $context
     * @param PropertyMappingConfigurationInterface $configuration
     * @return void
     * @throws TypeConverterException
     */
    protected function setNodeProperties($nodeLike, NodeType $nodeType, array $properties, ContentRepositoryContext $context, PropertyMappingConfigurationInterface $configuration = null)
    {
        $nodeTypeProperties = $nodeType->getProperties();
        unset($properties['_lastPublicationDateTime']);
        foreach ($properties as $nodePropertyName => $nodePropertyValue) {
            if (substr($nodePropertyName, 0, 2) === '__') {
                continue;
            }
            $nodePropertyType = isset($nodeTypeProperties[$nodePropertyName]['type']) ? $nodeTypeProperties[$nodePropertyName]['type'] : null;
            switch ($nodePropertyType) {
                case 'reference':
                    $nodePropertyValue = $context->getNodeByIdentifier($nodePropertyValue);
                break;
                case 'references':
                    $nodeIdentifiers = json_decode($nodePropertyValue);
                    $nodePropertyValue = [];
                    if (is_array($nodeIdentifiers)) {
                        foreach ($nodeIdentifiers as $nodeIdentifier) {
                            $referencedNode = $context->getNodeByIdentifier($nodeIdentifier);
                            if ($referencedNode !== null) {
                                $nodePropertyValue[] = $referencedNode;
                            }
                        }
                    } elseif ($nodeIdentifiers !== null) {
                        throw new TypeConverterException(sprintf('node type "%s" expects an array of identifiers for its property "%s"', $nodeType->getName(), $nodePropertyName), 1383587419);
                    }
                break;
                case 'DateTime':
                    if ($nodePropertyValue !== '' && ($nodePropertyValue = \DateTime::createFromFormat(\DateTime::W3C, $nodePropertyValue)) !== false) {
                        $nodePropertyValue->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                    } else {
                        $nodePropertyValue = null;
                    }
                break;
                case 'integer':
                    $nodePropertyValue = intval($nodePropertyValue);
                break;
                case 'boolean':
                    if (is_string($nodePropertyValue)) {
                        $nodePropertyValue = $nodePropertyValue === 'true' ? true : false;
                    }
                break;
                case 'array':
                    $nodePropertyValue = json_decode($nodePropertyValue, true);
                break;
            }
            if (substr($nodePropertyName, 0, 1) === '_') {
                $nodePropertyName = substr($nodePropertyName, 1);
                ObjectAccess::setProperty($nodeLike, $nodePropertyName, $nodePropertyValue);
                continue;
            }

            if (!isset($nodeTypeProperties[$nodePropertyName])) {
                if ($configuration !== null && $configuration->shouldSkipUnknownProperties()) {
                    continue;
                } else {
                    throw new TypeConverterException(sprintf('Node type "%s" does not have a property "%s" according to the schema', $nodeType->getName(), $nodePropertyName), 1359552744);
                }
            }
            $innerType = $nodePropertyType;
            if ($nodePropertyType !== null) {
                try {
                    $parsedType = TypeHandling::parseType($nodePropertyType);
                    $innerType = $parsedType['elementType'] ?: $parsedType['type'];
                } catch (InvalidTypeException $exception) {
                }
            }

            if (is_string($nodePropertyValue) && is_string($innerType) && $this->objectManager->isRegistered($innerType) && $nodePropertyValue !== '') {
                $nodePropertyValue = $this->propertyMapper->convert(json_decode($nodePropertyValue, true), $nodePropertyType, $configuration);
            }
            $nodeLike->setProperty($nodePropertyName, $nodePropertyValue);
        }
    }

    /**
     * Prepares the context properties for the nodes based on the given workspace and dimensions
     *
     * @param string $workspaceName
     * @param PropertyMappingConfigurationInterface $configuration
     * @param array $dimensions
     * @return array
     */
    protected function prepareContextProperties($workspaceName, PropertyMappingConfigurationInterface $configuration = null, array $dimensions = null)
    {
        $contextProperties = [
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => false,
            'removedContentShown' => false
        ];
        if ($configuration !== null && $configuration->getConfigurationValue(NodeConverter::class, self::INVISIBLE_CONTENT_SHOWN) === true) {
            $contextProperties['invisibleContentShown'] = true;
        }
        if ($workspaceName !== 'live') {
            $contextProperties['invisibleContentShown'] = true;
            if ($configuration !== null && $configuration->getConfigurationValue(NodeConverter::class, self::REMOVED_CONTENT_SHOWN) === true) {
                $contextProperties['removedContentShown'] = true;
            }
        }

        if ($dimensions !== null) {
            $contextProperties['dimensions'] = $dimensions;
        }

        return $contextProperties;
    }
}
