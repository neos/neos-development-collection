<?php
namespace TYPO3\Neos\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * The content element wrapping service adds the necessary markup around
 * a content element such that it can be edited using the Content Module
 * of the Neos Backend.
 *
 * @Flow\Scope("singleton")
 */
class ContentElementWrappingService {

	/**
	 * @Flow\Inject
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * @Flow\Inject
	 * @var HtmlAugmenter
	 */
	protected $htmlAugmenter;

	/**
	 * Wrap the $content identified by $node with the needed markup for the backend.
	 *
	 * @param NodeInterface $node
	 * @param string $typoScriptPath
	 * @param string $content
	 * @return string
	 */
	public function wrapContentObject(NodeInterface $node, $typoScriptPath, $content) {
		/** @var $contentContext ContentContext */
		$contentContext = $node->getContext();
		if ($contentContext->getWorkspaceName() === 'live' || !$this->accessDecisionManager->hasAccessToResource('TYPO3_Neos_Backend_GeneralAccess')) {
			return $content;
		}
		$nodeType = $node->getNodeType();
		$attributes = array();
		$attributes['typeof'] = 'typo3:' . $nodeType->getName();
		$attributes['about'] = $node->getContextPath();
		$attributes['class'] = '';

		if (!$nodeType->isOfType('TYPO3.Neos:Document')) {
			if ($nodeType->isOfType('TYPO3.Neos:ContentCollection')) {
				$attributes['rel'] = 'typo3:content-collection';
			} else {
				$attributes['class'] = 'neos-contentelement';
			}

			if ($node->isHidden()) {
				$attributes['class'] .= ' neos-contentelement-hidden';
			}
			if ($node->isRemoved()) {
				$attributes['class'] .= ' neos-contentelement-removed';
			}

			$uiConfiguration = $nodeType->hasConfiguration('ui') ? $nodeType->getConfiguration('ui') : array();
			if ((isset($uiConfiguration['inlineEditable']) && $uiConfiguration['inlineEditable'] !== TRUE) || (!isset($uiConfiguration['inlineEditable']) && !$this->hasInlineEditableProperties($node))) {
				$attributes['class'] .= ' neos-not-inline-editable';
			}

			$attributes['class'] = trim($attributes['class']);
			$attributes['tabindex'] = 0;
		} else {
			$attributes['data-__sitename'] = $contentContext->getCurrentSite()->getName();
			$attributes['data-__siteroot'] = sprintf('/sites/%s@%s', $contentContext->getCurrentSite()->getNodeName(), $contentContext->getWorkspace()->getName());
		}
		$attributes['data-neos-__workspacename'] = $node->getWorkspace()->getName();
		$attributes['data-neos-_typoscript-path'] = $typoScriptPath;

		$attributes = $this->addNodePropertyAttributes($node, $attributes);

		$attributes['data-neos-__nodetype'] = $nodeType->getName();
		return $this->htmlAugmenter->addAttributes($content, $attributes);
	}

	/**
	 * Adds node properties to the given $attributes collection and returns the extended array
	 *
	 * @param NodeInterface $node
	 * @param array $attributes
	 * @return array the merged attributes
	 */
	public function addNodePropertyAttributes(NodeInterface $node, array $attributes) {
		foreach ($node->getNodeType()->getProperties() as $propertyName => $propertyConfiguration) {
			if (substr($propertyName, 0, 2) === '__') {
				// skip fully-private properties
				continue;
			}
			/** @var $contentContext ContentContext */
			$contentContext = $node->getContext();
			if ($propertyName === '_name' && $node === $contentContext->getCurrentSiteNode()) {
				// skip the node name of the site node
				continue;
			}
			$dataType = isset($propertyConfiguration['type']) ? $propertyConfiguration['type'] : 'string';
			$dasherizedPropertyName = $this->dasherize($propertyName);
			$attributes['data-neos-' . $dasherizedPropertyName] = $this->getNodeProperty($node, $propertyName, $dataType);
			if ($dataType !== 'string') {
				$prefixedDataType = $dataType === 'jsonEncoded' ? 'typo3:jsonEncoded' : 'xsd:' . $dataType;
				$attributes['data-neosdatatype-' . $dasherizedPropertyName] = $prefixedDataType;
			}
		}
		return $attributes;
	}

	/**
	 * @param NodeInterface $node
	 * @param string $propertyName
	 * @param string $dataType
	 * @return string
	 */
	protected function getNodeProperty(NodeInterface $node, $propertyName, &$dataType) {
		if (substr($propertyName, 0, 1) === '_') {
			$propertyValue = ObjectAccess::getProperty($node, substr($propertyName, 1));
		} else {
			$propertyValue = $node->getProperty($propertyName);
		}

		// Enforce an integer value for integer properties as otherwise javascript will give NaN and VIE converts it to an array containing 16 times 'NaN'
		if ($dataType === 'integer') {
			$propertyValue = (integer)$propertyValue;
		}

		// Serialize boolean values to String
		if ($dataType === 'boolean') {
			return $propertyValue ? 'true' : 'false';
		}

		// Serialize date values to String
		if ($dataType === 'date' && $propertyValue instanceof \DateTime) {
			return $propertyValue->format('Y-m-d');
		}

		// Serialize node references to node identifiers
		if ($dataType === 'references') {
			$nodeIdentifiers = array();
			if (is_array($propertyValue)) {
				/** @var $subNode NodeInterface */
				foreach ($propertyValue as $subNode) {
					$nodeIdentifiers[] = $subNode->getIdentifier();
				}
			}
			return json_encode($nodeIdentifiers);
		}

		// Serialize node reference to node identifier
		if ($dataType === 'reference') {
			if ($propertyValue instanceof NodeInterface) {
				return $propertyValue->getIdentifier();
			} else {
				return '';
			}
		}

		// Serialize objects to JSON strings
		if (is_object($propertyValue) && $this->objectManager->isRegistered($dataType)) {
			$dataType = 'jsonEncoded';
			$gettableProperties = ObjectAccess::getGettableProperties($propertyValue);
			$convertedProperties = array();
			foreach ($gettableProperties as $key => $value) {
				if (is_object($value)) {
					$entityIdentifier = $this->persistenceManager->getIdentifierByObject($value);
					if ($entityIdentifier !== NULL) {
						$value = $entityIdentifier;
					}
				}
				$convertedProperties[$key] = $value;
			}
			return json_encode($convertedProperties);
		}
		return $propertyValue === NULL ? '' : $propertyValue;
	}

	/**
	 * @param NodeInterface $node
	 * @return boolean
	 */
	protected function hasInlineEditableProperties(NodeInterface $node) {
		foreach (array_values($node->getNodeType()->getProperties()) as $propertyConfiguration) {
			if (isset($propertyConfiguration['ui']['inlineEditable']) && $propertyConfiguration['ui']['inlineEditable'] === TRUE) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Converts camelCased strings to lower cased and non-camel-cased strings
	 *
	 * @param string $value
	 * @return string
	 */
	protected function dasherize($value) {
		return strtolower(trim(preg_replace('/[A-Z]/', '-$0', $value), '-'));
	}

}
