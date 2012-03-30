<?php
namespace TYPO3\TYPO3\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The content element wrapping service adds the necessary markup around
 * a content element such that it can be edited using the Content Module
 * of the TYPO3 Backend.
 *
 * @FLOW3\Scope("singleton")
 */
class ContentElementWrappingService {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * Wrap the $content identified by $node with the needed markup for
	 * the backend.
	 * $parameters can be used to further pass parameters to the content element.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $content
	 * @param boolean $isPage
	 */
	public function wrapContentObject(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $content, $isPage = FALSE) {
		try {
			$this->accessDecisionManager->decideOnResource('TYPO3_TYPO3_Backend_BackendController');
		} catch (\TYPO3\FLOW3\Security\Exception\AccessDeniedException $e) {
			return $content;
		}

		$tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('div');
		$tagBuilder->forceClosingTag(TRUE);
		$tagBuilder->addAttribute('data-__nodepath', $node->getContextPath());
		$tagBuilder->addAttribute('data-__workspacename', $node->getWorkspace()->getName());

		$contentType = $this->contentTypeManager->getContentType($node->getContentType());

		foreach ($contentType->getProperties() as $propertyName => $propertyConfiguration) {
			if ($propertyName[0] === '_') {
				$propertyValue = \TYPO3\FLOW3\Reflection\ObjectAccess::getProperty($node, substr($propertyName, 1));
			} else {
				$propertyValue = $node->getProperty($propertyName);
			}
				// Serialize boolean values to String
			if (isset($propertyConfiguration['type']) && $propertyConfiguration['type'] === 'boolean') {
				$propertyValue = ($propertyValue ? 'true' : 'false');
			}

				// Serialize date values to String
			if ($propertyValue !== NULL && isset($propertyConfiguration['type']) && $propertyConfiguration['type'] === 'date') {
				$propertyValue = $propertyValue->format('Y-m-d');
			}

				// Serialize objects to JSON strings
			if (is_object($propertyValue) && $propertyValue !== NULL && isset($propertyConfiguration['type']) && $this->objectManager->isRegistered($propertyConfiguration['type'])) {
				$gettableProperties = \TYPO3\FLOW3\Reflection\ObjectAccess::getGettableProperties($propertyValue);
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
			}

			$tagBuilder->addAttribute('data-' . $propertyName, $propertyValue);
		}

		if (!$isPage) {
				// add CSS classes
			$cssClasses = array('t3-contentelement');
			$cssClasses[] = str_replace(array(':', '.'), '-', strtolower($contentType->getName()));
			if ($node->isHidden()) {
				$cssClasses[] = 't3-contentelement-hidden';
			}

			$tagBuilder->addAttribute('class', implode(' ', $cssClasses));
			$tagBuilder->addAttribute('data-__contenttype', $contentType->getName());
		} else {
			$tagBuilder->addAttribute('id', 't3-page-metainformation');
			$tagBuilder->addAttribute('data-__sitename', $node->getContext()->getCurrentSite()->getName());
			$tagBuilder->addAttribute('data-__siteroot', sprintf(
				'/sites/%s@%s',
				$node->getContext()->getCurrentSite()->getNodeName(),
				$node->getContext()->getWorkspace()->getName()
			));
		}

		$tagBuilder->setContent($content);
		return $tagBuilder->render();
	}
}
?>