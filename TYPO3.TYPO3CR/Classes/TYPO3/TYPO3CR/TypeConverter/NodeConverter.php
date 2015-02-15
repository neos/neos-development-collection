<?php
namespace TYPO3\TYPO3CR\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Error;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Property\Exception\TypeConverterException;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Security\Context;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Service\Context as TYPO3CRContext;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeService;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Exception\NodeException;

/**
 * An Object Converter for Nodes which can be used for routing (but also for other
 * purposes) as a plugin for the Property Mapper.
 *
 * @Flow\Scope("singleton")
 */
class NodeConverter extends AbstractTypeConverter {

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
	 * @var NodeService
	 */
	protected $nodeService;

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\TYPO3CR\Domain\Model\NodeInterface';

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
	 * @return mixed An object or \TYPO3\Flow\Error\Error if the input format is not supported or could not be converted for other reasons
	 * @throws NodeException
	 */
	public function convertFrom($source, $targetType = NULL, array $subProperties = array(), PropertyMappingConfigurationInterface $configuration = NULL) {
		if (is_string($source)) {
			$source = array('__contextNodePath' => $source);
		}

		if (!is_array($source) || !isset($source['__contextNodePath'])) {
			return new Error('Could not convert ' . gettype($source) . ' to Node object, a valid absolute context node path as a string or array is expected.', 1302879936);
		}

		preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $source['__contextNodePath'], $matches);
		if (!isset($matches['NodePath'])) {
			return new Error('Could not convert array to Node object because the node path was invalid.', 1285162903);
		}
		$nodePath = $matches['NodePath'];

		$workspaceName = (isset($matches['WorkspaceName']) && $matches['WorkspaceName'] !== '' ? $matches['WorkspaceName'] : 'live');

		$dimensions = NULL;
		if (isset($matches['Dimensions'])) {
			$dimensions = $this->contextFactory->parseDimensionValueStringToArray($matches['Dimensions']);
		}

		$context = $this->contextFactory->create($this->prepareContextProperties($workspaceName, $configuration, $dimensions));
		$workspace = $context->getWorkspace(FALSE);
		if (!$workspace) {
			return new Error(sprintf('Could not convert the given source to Node object because the workspace "%s" as specified in the context node path does not exist.', $workspaceName), 1383577859);
		}

		$node = $context->getNode($nodePath);
		if (!$node) {
			return new Error(sprintf('Could not convert array to Node object because the node "%s" does not exist.', $nodePath), 1370502328);
		}

		$targetNodeType = NULL;
		if (isset($source['_nodeType'])) {
			$source['_nodeType'] = $this->nodeTypeManager->getNodeType($source['_nodeType']);
			if ($source['_nodeType'] !== $node->getNodeType()) {
				if ($context->getWorkspace()->getName() === 'live') {
					throw new NodeException('Could not convert the node type in live workspace');
				}
				$targetNodeType = $source['_nodeType'];
			}
		}
		$this->setNodeProperties($node, $node->getNodeType(), $source, $context, $configuration);
		if ($targetNodeType !== NULL) {
			$this->nodeService->setDefaultValues($node, $targetNodeType);
			$this->nodeService->createChildNodes($node, $targetNodeType);
		}

		return $node;
	}

	/**
	 * Iterates through the given $properties setting them on the specified $node using the appropriate TypeConverters.
	 *
	 * @param object $nodeLike
	 * @param NodeType $nodeType
	 * @param array $properties
	 * @param TYPO3CRContext $context
	 * @param PropertyMappingConfigurationInterface $configuration
	 * @return void
	 * @throws TypeConverterException
	 */
	protected function setNodeProperties($nodeLike, NodeType $nodeType, array $properties, TYPO3CRContext $context, PropertyMappingConfigurationInterface $configuration = NULL) {
		$nodeTypeProperties = $nodeType->getProperties();
		unset($properties['_lastPublicationDateTime']);
		foreach ($properties as $nodePropertyName => $nodePropertyValue) {
			if (substr($nodePropertyName, 0, 2) === '__') {
				continue;
			}
			$nodePropertyType = isset($nodeTypeProperties[$nodePropertyName]['type']) ? $nodeTypeProperties[$nodePropertyName]['type'] : NULL;
			switch ($nodePropertyType) {
				case 'reference':
					$nodePropertyValue = $context->getNodeByIdentifier($nodePropertyValue);
				break;
				case 'references':
					$nodeIdentifiers = json_decode($nodePropertyValue);
					$nodePropertyValue = array();
					if (is_array($nodeIdentifiers)) {
						foreach ($nodeIdentifiers as $nodeIdentifier) {
							$referencedNode = $context->getNodeByIdentifier($nodeIdentifier);
							if ($referencedNode !== NULL) {
								$nodePropertyValue[] = $referencedNode;
							}
						}
					} else {
						throw new TypeConverterException(sprintf('node type "%s" expects an array of identifiers for its property "%s"', $nodeType->getName(), $nodePropertyName), 1383587419);
					}
				break;
				case 'DateTime':
					if ($nodePropertyValue !== '') {
						$nodePropertyValue = \DateTime::createFromFormat(\DateTime::W3C, $nodePropertyValue);
						$nodePropertyValue->setTimezone(new \DateTimeZone(date_default_timezone_get()));
					} else {
						$nodePropertyValue = NULL;
					}
				break;
				case 'integer':
					$nodePropertyValue = intval($nodePropertyValue);
				break;
				case 'boolean':
					if (is_string($nodePropertyValue)) {
						$nodePropertyValue = $nodePropertyValue === 'true' ? TRUE : FALSE;
					}
				break;
				case 'array':
					$nodePropertyValue = json_decode($nodePropertyValue);
				break;
			}
			if (substr($nodePropertyName, 0, 1) === '_') {
				$nodePropertyName = substr($nodePropertyName, 1);
				ObjectAccess::setProperty($nodeLike, $nodePropertyName, $nodePropertyValue);
				continue;
			}
			if (!isset($nodeTypeProperties[$nodePropertyName])) {
				throw new TypeConverterException(sprintf('Node type "%s" does not have a property "%s" according to the schema', $nodeType->getName(), $nodePropertyName), 1359552744);
			}
			$innerType = $nodePropertyType;
			if ($nodePropertyType !== NULL) {
				try {
					$parsedType = \TYPO3\Flow\Utility\TypeHandling::parseType($nodePropertyType);
					$innerType = $parsedType['elementType'] ?: $parsedType['type'];
				} catch(\TYPO3\Flow\Utility\Exception\InvalidTypeException $exception) {
				}
			}

			if (is_string($nodePropertyValue) && $this->objectManager->isRegistered($innerType) && $nodePropertyValue !== '') {
				$nodePropertyValue = $this->propertyMapper->convert(json_decode($nodePropertyValue, TRUE), $nodePropertyType, $configuration);
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
	protected function prepareContextProperties($workspaceName, PropertyMappingConfigurationInterface $configuration = NULL, array $dimensions = NULL) {
		$contextProperties = array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => FALSE,
			'removedContentShown' => FALSE
		);
		if ($workspaceName !== 'live') {
			$contextProperties['invisibleContentShown'] = TRUE;
			if ($configuration !== NULL && $configuration->getConfigurationValue('TYPO3\TYPO3CR\TypeConverter\NodeConverter', self::REMOVED_CONTENT_SHOWN) === TRUE) {
				$contextProperties['removedContentShown'] = TRUE;
			}
		}

		if ($dimensions !== NULL) {
			$contextProperties['dimensions'] = $dimensions;
		}

		return $contextProperties;
	}
}
