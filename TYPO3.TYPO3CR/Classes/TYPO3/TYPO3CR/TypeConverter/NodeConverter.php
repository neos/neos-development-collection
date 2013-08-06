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
use TYPO3\TYPO3CR\Exception\NodeException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * An Object Converter for Nodes which can be used for routing (but also for other
 * purposes) as a plugin for the Property Mapper.
 *
 * @Flow\Scope("singleton")
 */
class NodeConverter extends \TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter {

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
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\TYPO3CR\Domain\Model\NodeInterface';

	/**
	 * @var integer
	 */
	protected $priority = 1;


	/**
	 * Converts the specified node path into a Node.
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
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration not used
	 * @return mixed An object or \TYPO3\Flow\Error\Error if the input format is not supported or could not be converted for other reasons
	 * @throws \Exception
	 */
	public function convertFrom($source, $targetType = NULL, array $subProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
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

		$workspaceName = (isset($matches['WorkspaceName']) ? $matches['WorkspaceName'] : 'live');

		try {
			$node = $this->createNode($nodePath, $workspaceName, $configuration);
		} catch (NodeException $exception) {
			return new Error($exception->getMessage(), $exception->getCode());
		}
		$this->setNodeProperties($node, $node->getNodeType(), $source);
		return $node;
	}

	/**
	 * Iterates through the given $properties setting them on the specified $node using the appropriate TypeConverters.
	 *
	 * @param object $nodeLike
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType
	 * @param array $properties
	 * @throws \TYPO3\Flow\Property\Exception\TypeConverterException
	 * @return void
	 */
	protected function setNodeProperties($nodeLike, \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType, array $properties) {
		$nodeTypeProperties = $nodeType->getProperties();
		foreach ($properties as $nodePropertyName => $nodePropertyValue) {
			if (substr($nodePropertyName, 0, 2) === '__') {
				continue;
			}
			if (substr($nodePropertyName, 0, 1) === '_') {
				$nodePropertyName = substr($nodePropertyName, 1);

				// TODO: Hack: we need to create DateTime objects for some properties of Node
				if (($nodePropertyName === 'hiddenBeforeDateTime' || $nodePropertyName === 'hiddenAfterDateTime') && is_string($nodePropertyValue)) {
					if ($nodePropertyValue !== '') {
						$nodePropertyValue = \DateTime::createFromFormat('!Y-m-d', $nodePropertyValue);
					} else {
						$nodePropertyValue = NULL;
					}
				}
				\TYPO3\Flow\Reflection\ObjectAccess::setProperty($nodeLike, $nodePropertyName, $nodePropertyValue);
				continue;
			}
			if (!isset($nodeTypeProperties[$nodePropertyName])) {
				throw new \TYPO3\Flow\Property\Exception\TypeConverterException(sprintf('node type "%s" does not have a property "%s" according to the schema', $nodeType->getName(), $nodePropertyName), 1359552744);
			}
			if (isset($nodeTypeProperties[$nodePropertyName]['type'])) {
				$targetType = $nodeTypeProperties[$nodePropertyName]['type'];
				if ($this->objectManager->isRegistered($targetType)) {
					// If $nodePropertyValue is not empty then convert it
					if ($nodePropertyValue !== '') {
						$nodePropertyValue = $this->propertyMapper->convert(json_decode($nodePropertyValue, TRUE), $targetType);
					}
				}
			}
			$nodeLike->setProperty($nodePropertyName, $nodePropertyValue);
		}
	}

	/**
	 * Tries to fetch the Node object based on the given path and workspace.
	 *
	 * @param string $nodePath
	 * @param string $workspaceName
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException
	 */
	protected function createNode($nodePath, $workspaceName, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$nodeContext = $this->createContext($workspaceName, $configuration);
		$workspace = $nodeContext->getWorkspace(FALSE);
		if (!$workspace) {
			throw new NodeException(sprintf('Could not convert %s to Node object because the workspace "%s" as specified in the context node path does not exist.', $nodePath, $workspaceName), 1370502313);
		}

		$node = $nodeContext->getNode($nodePath);
		if (!$node) {
			throw new NodeException(sprintf('Could not convert array to Node object because the node "%s" does not exist.', $nodePath), 1370502328);
		}

		return $node;
	}

	/**
	 * Creates the context for the nodes based on the given workspace.
	 *
	 * @param string $workspaceName
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \TYPO3\TYPO3CR\Domain\Service\ContextInterface
	 */
	protected function createContext($workspaceName, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$invisibleContentShown = FALSE;
		$removedContentShown = FALSE;
		if ($workspaceName !== 'live') {
			$invisibleContentShown = TRUE;
			if ($configuration !== NULL && $configuration->getConfigurationValue('TYPO3\TYPO3CR\TypeConverter\NodeConverter', self::REMOVED_CONTENT_SHOWN) === TRUE) {
				$removedContentShown = TRUE;
			}
		}

		return $this->contextFactory->create(array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => $invisibleContentShown,
			'removedContentShown' => $removedContentShown
		));
	}
}
?>
