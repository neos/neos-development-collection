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

use \TYPO3\Flow\Error\Error;
use \TYPO3\Neos\Domain\Service\ContentContext;
use \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * An Object Converter for NodeTemplates.
 *
 * @Flow\Scope("singleton")
 */
class NodeTemplateConverter extends NodeConverter {

	/**
	 * A pattern that separates the node content object type from the node type
	 */
	const EXTRACT_CONTENT_TYPE_PATTERN = '/^\\\\?(?P<type>TYPO3\\\TYPO3CR\\\Domain\\\Model\\\NodeTemplate)(?:<\\\\?(?P<contentType>[a-zA-Z0-9\\\\\:\.]+)>)?/';

	/**
	 * @var array
	 */
	protected $sourceTypes = array('array');

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\TYPO3CR\Domain\Model\NodeTemplate';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * Converts the specified node path into a Node.
	 *
	 * The node path must be an absolute context node path and can be specified as a string or as an array item with the
	 * key "__contextNodePath". The latter case is for updating existing nodes.
	 *
	 * This conversion method supports creation of new nodes because new nodes
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
	 *
	 * @param string|array $source Either a string or array containing the absolute context node path which identifies the node. For example "/sites/mysitecom/homepage/about@user-admin"
	 * @param string $targetType not used
	 * @param array $subProperties not used
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration not used
	 * @return mixed An object or \TYPO3\Flow\Error\Error if the input format is not supported or could not be converted for other reasons
	 * @throws \Exception
	 */
	public function convertFrom($source, $targetType, array $subProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$nodeTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
		$contentType = $this->extractContentType($targetType, $source);
		$nodeTemplate->setContentType($contentType);
		$this->setNodeProperties($nodeTemplate, $source);
		return $nodeTemplate;
	}

	/**
	 * Detects the requested content type and returns a corresponding ContentType instance.
	 *
	 * @param string $targetType
	 * @param array $source
	 * @return \TYPO3\TYPO3CR\Domain\Model\ContentType
	 */
	protected function extractContentType($targetType, array $source) {
		if (isset($source['__contentType'])) {
			$contentTypeName = $source['__contentType'];
		} else {
			$matches = array();
			preg_match(self::EXTRACT_CONTENT_TYPE_PATTERN, $targetType, $matches);
			if (isset($matches['contentType'])) {
				$contentTypeName = $matches['contentType'];
			} else {
				$contentTypeName = 'unstructured';
			}
		}
		return $this->contentTypeManager->getContentType($contentTypeName);
	}
}
?>