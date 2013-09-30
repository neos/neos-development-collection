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
use TYPO3\Flow\Reflection\ObjectAccess;
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
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * Wrap the $content identified by $node with the needed markup for
	 * the backend.
	 * $parameters can be used to further pass parameters to the content element.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Node $node
	 * @param string $typoscriptPath
	 * @param string $content
	 * @param boolean $isPage
	 * @return string
	 */
	public function wrapContentObject(\TYPO3\TYPO3CR\Domain\Model\Node $node, $typoscriptPath, $content, $isPage = FALSE) {
		$tagBuilder = $this->wrapContentObjectAndReturnTagBuilder($node, $typoscriptPath, $content, $isPage);
		return $tagBuilder->render();
	}

	/**
	 * Wrap the $content identified by $node with the needed markup for
	 * the backend, and return the tag builder instance for further modification.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Node $node
	 * @param string $typoscriptPath
	 * @param string $content
	 * @param boolean $isPage
	 * @return \TYPO3\Fluid\Core\ViewHelper\TagBuilder
	 */
	public function wrapContentObjectAndReturnTagBuilder(\TYPO3\TYPO3CR\Domain\Model\Node $node, $typoscriptPath, $content, $isPage = FALSE) {
		$nodeType = $node->getNodeType();

		$tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('div');
		$tagBuilder->forceClosingTag(TRUE);
		if (!$node->isRemoved()) {
			$tagBuilder->setContent($content);
		}

		if (!$isPage) {
			$cssClasses = array(
				'neos-contentelement',
				str_replace(array(':', '.'), '-', strtolower($nodeType->getName()))
			);
			$tagBuilder->addAttribute('class', implode(' ', $cssClasses));
			$tagBuilder->addAttribute('id', 'c' . $node->getIdentifier());
		} else {
			$cssClasses = array();
		}

		if ($this->accessDecisionManager->hasAccessToResource('TYPO3_Neos_Backend_GeneralAccess') === FALSE) {
			return $tagBuilder;
		}

		$tagBuilder->addAttribute('typeof', 'typo3:' . $nodeType->getName());
		$tagBuilder->addAttribute('about', $node->getContextPath());
		$tagBuilder->addAttribute('tabindex', '0');

		$this->addDataAttribute($tagBuilder, '__workspacename', $node->getWorkspace()->getName());
		$this->addDataAttribute($tagBuilder, '_typoscriptPath', $typoscriptPath);
		$hasInlineEditableProperties = FALSE;
		foreach ($nodeType->getProperties() as $propertyName => $propertyConfiguration) {
			if ($propertyName[0] === '_' && $propertyName[1] === '_') {
				// skip fully-private properties
				continue;
			}
			$dataType = isset($propertyConfiguration['type']) ? $propertyConfiguration['type'] : 'string';
			if ($propertyName[0] === '_') {
				$propertyValue = ObjectAccess::getProperty($node, substr($propertyName, 1));
			} else {
				$propertyValue = $node->getProperty($propertyName);
			}
			// Serialize boolean values to String
			if ($dataType === 'boolean') {
				$propertyValue = ($propertyValue ? 'true' : 'false');
			}

			// Serialize date values to String
			if ($propertyValue instanceof \DateTime && $dataType === 'date') {
				$propertyValue = $propertyValue->format('Y-m-d');
			}

			// Serialize node references to node identifiers
			if ($dataType === 'references') {
				$nodeIdentifiers = array();
				if (is_array($propertyValue)) {
					foreach ($propertyValue as $subNode) {
						$nodeIdentifiers[] = $subNode->getIdentifier();
					}
				}
				$propertyValue = json_encode($nodeIdentifiers);
			}

			// Serialize node reference to node identifier
			if ($dataType === 'reference') {
				if ($propertyValue instanceof NodeInterface) {
					$propertyValue = $propertyValue->getIdentifier();
				} else {
					$propertyValue = '';
				}
			}

			// Serialize objects to JSON strings
			if (is_object($propertyValue) && $propertyValue !== NULL && isset($propertyConfiguration['type']) && $this->objectManager->isRegistered($propertyConfiguration['type'])) {
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
				$propertyValue = json_encode($convertedProperties);
				$dataType = 'jsonEncoded';
			}

			$this->addDataAttribute($tagBuilder, $propertyName, $propertyValue, $dataType);

			if (isset($propertyConfiguration['ui']) && isset($propertyConfiguration['ui']['inlineEditable']) && $propertyConfiguration['ui']['inlineEditable'] === TRUE) {
				$hasInlineEditableProperties = TRUE;
			}
		}

		if (!$isPage) {
			if ($node->isHidden()) {
				$cssClasses[] = 'neos-contentelement-hidden';
			}
			if ($node->isRemoved()) {
				$cssClasses[] = 'neos-contentelement-removed';
			}

			$uiConfiguration = $nodeType->hasUi() ? $nodeType->getUi() : array();
			if ((!isset($uiConfiguration['inlineEditable']) && !$hasInlineEditableProperties) || (isset($uiConfiguration['inlineEditable']) && $uiConfiguration['inlineEditable'] !== TRUE)) {
				$cssClasses[] = 'neos-not-inline-editable';
			}
			$tagBuilder->addAttribute('class', implode(' ', $cssClasses));

			$this->addDataAttribute($tagBuilder, '__nodetype', $nodeType->getName());
		} else {
			$tagBuilder->addAttribute('id', 'neos-page-metainformation');
			$tagBuilder->addAttribute('data-__sitename', $node->getContext()->getCurrentSite()->getName());
			$tagBuilder->addAttribute('data-__siteroot', sprintf(
				'/sites/%s@%s',
				$node->getContext()->getCurrentSite()->getNodeName(),
				$node->getContext()->getWorkspace()->getName()
			));
		}

		return $tagBuilder;
	}

	/**
	 * Add a data attribute with the property metadata to the content.
	 *
	 * We are converting the lowerCamelCased versions of properties to dash-er-ized versions,
	 * because properties are case-insensitive, but the UI needs to be able to work with the
	 * proper lowerCamelCased versions.
	 *
	 * @param \TYPO3\Fluid\Core\ViewHelper\TagBuilder $tagBuilder
	 * @param string $propertyName
	 * @param string $propertyValue
	 * @param string $dataType
	 * @return void
	 */
	protected function addDataAttribute(\TYPO3\Fluid\Core\ViewHelper\TagBuilder $tagBuilder, $propertyName, $propertyValue, $dataType = 'string') {

		$dasherizedPropertyName = preg_replace_callback('/([A-Z])/', function($character) {
			return '-' . strtolower($character[1]);
		}, $propertyName);
		$tagBuilder->addAttribute('data-neos-' . $dasherizedPropertyName, $propertyValue);

		$dataType = $this->getDataTypeCurie($dataType);
		if ($dataType !== 'xsd:string') {
			$tagBuilder->addAttribute('data-neosdatatype-' . $dasherizedPropertyName, $dataType);
		}
	}

	/**
	 * Map a data type from the node type definition to a correct
	 * CURIE.
	 *
	 * @param string $dataType
	 * @return string
	 */
	protected function getDataTypeCurie($dataType) {
		switch ($dataType) {
			case 'jsonEncoded':
				return 'typo3:jsonEncoded';
			default:
				return 'xsd:' . $dataType;
		}
	}

}
