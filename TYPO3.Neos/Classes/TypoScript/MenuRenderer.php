<?php
namespace TYPO3\TYPO3\TypoScript;

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
 * A TypoScript Menu object
 *
 * @FLOW3\Scope("prototype")
 */
class MenuRenderer extends \TYPO3\TypoScript\TypoScriptObjects\FluidRenderer {

	/**
	 * Hard limit for the maximum number of levels supported by this menu
	 */
	const MAXIMUM_LEVELS_LIMIT = 100;

	const STATE_NORMAL = 'normal';
	const STATE_CURRENT = 'current';
	const STATE_ACTIVE = 'active';

	/**
	 * @var string
	 */
	protected $templatePath = 'resource://TYPO3.TYPO3/Private/Templates/TypoScriptObjects/Menu.html';

	/**
	 * The first navigation level which should be rendered.
	 *
	 * 1 = first level of the site
	 * 2 = second level of the site
	 * ...
	 * 0  = same level as the current page
	 * -1 = one level above the current page
	 * -2 = two levels above the current page
	 * ...
	 *
	 * @var integer
	 */
	protected $entryLevel = 1;

	/**
	 * The last navigation level which should be rendered.
	 *
	 * 1 = first level of the site
	 * 2 = second level of the site
	 * ...
	 * 0  = same level as the current page
	 * -1 = one level above the current page
	 * -2 = two levels above the current page
	 * ...
	 *
	 * @var integer
	 */
	protected $lastLevel;

	/**
	 * Maximum number of levels which should be rendered in this menu.
	 *
	 * @var integer
	 */
	protected $maximumLevels = 1;

	/**
	 * An internal cache for the built menu items array.
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * @param integer $entryLevel
	 * @return void
	 */
	public function setEntryLevel($entryLevel) {
		$this->entryLevel = $entryLevel;
	}

	/**
	 * @param integer $maximumLevels
	 * @return void
	 */
	public function setMaximumLevels($maximumLevels) {
		if ($maximumLevels > self::MAXIMUM_LEVELS_LIMIT) {
			$maximumLevels = self::MAXIMUM_LEVELS_LIMIT;
		}
		$this->maximumLevels = $maximumLevels;
	}

	/**
	 * @param integer $lastLevel
	 */
	public function setLastLevel($lastLevel) {
		if ($lastLevel > self::MAXIMUM_LEVELS_LIMIT) {
			$lastLevel = self::MAXIMUM_LEVELS_LIMIT;
		}
		$this->lastLevel = $lastLevel;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $context
	 * @return string
	 */
	public function evaluate($context) {
		$this['items'] = $this->getItems($context);
		return parent::evaluate($context);
	}

	/**
	 * Returns the menu items according to the defined settings.
	 *
	 * @return array
	 */
	public function getItems(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		if ($this->items === NULL) {
			$this->items = $this->buildItems($node->getContext());
		}
		return $this->items;
   }

	/**
	 * Builds the array of menu items containing those items which match the
	 * configuration set for this Menu object.
	 *
	 * @param \TYPO3\TYPO3\Domain\Service\ContentContext $contentContext
	 * @return array An array of menu items and further information
	 */
	protected function buildItems(\TYPO3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$entryParentNode = $this->findParentNodeInBreadcrumbPathByLevel($this->entryLevel, $contentContext);
		if ($entryParentNode === NULL) {
			return array();
		}
		$lastParentNode = ($this->lastLevel !== NULL) ? $this->findParentNodeInBreadcrumbPathByLevel($this->lastLevel, $contentContext) : NULL;

		return $this->buildRecursiveItemsArray($entryParentNode, $lastParentNode, $contentContext);
	}

	/**
	 * Recursively called method which builds the actual items array.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $entryParentNode The parent node whose children should be listed as items
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $lastParentNode The last parent node whose children should be listed. NULL = no limit defined through lastLevel
	 * @param \TYPO3\TYPO3\Domain\Service\ContentContext $contentContext $contentContext The current content context
	 * @param integer $currentLevel Level count for the recursion – don't use.
	 * @return array A nested array of menu item information
	 * @see buildItems()
	 */
	private function buildRecursiveItemsArray(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $entryParentNode, $lastParentNode, \TYPO3\TYPO3\Domain\Service\ContentContext $contentContext, $currentLevel = 1) {
		$items = array();
		foreach ($entryParentNode->getChildNodes('TYPO3.TYPO3:Page,TYPO3.TYPO3:Shortcut') as $currentNode) {
			if ($currentNode->isVisible() === FALSE || $currentNode->isHiddenInIndex() === TRUE || $currentNode->isAccessible() === FALSE) {
				continue;
			}

			$item = array(
				'label' => $currentNode->getProperty('title'),
				'node' => $currentNode,
				'state' => self::STATE_NORMAL
			);
			if ($currentNode === $contentContext->getCurrentNode()) {
				$item['state'] = self::STATE_CURRENT;
			}

			if ($currentLevel < $this->maximumLevels && $entryParentNode !== $lastParentNode) {
				$subItems = $this->buildRecursiveItemsArray($currentNode, $lastParentNode, $contentContext, $currentLevel + 1);
				if ($subItems !== array()) {
					$item['subItems'] = $subItems;
					if ($item['state'] !== self::STATE_CURRENT) {
						foreach ($subItems as $subItem) {
							if ($subItem['state'] === self::STATE_CURRENT || $subItem['state'] === self::STATE_ACTIVE) {
								$item['state'] = self::STATE_ACTIVE;
								break;
							}
						}
					}
				}
			}
			$items[] = $item;
		}
		return $items;
	}

	/**
	 * Finds the node in the current breadcrumb path between current site node and
	 * current node whose level matches the specified entry level.
	 *
	 * @param integer $givenSiteLevel The site level child nodes of the to be found parent node should have. See $this->entryLevel for possible values.
	 * @param \TYPO3\TYPO3\Domain\Service\ContentContext $contentContext
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The parent node of the node at the specified level or NULL if none was found
	 */
	private function findParentNodeInBreadcrumbPathByLevel($givenSiteLevel, \TYPO3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$parentNode = NULL;
		$breadcrumbNodes = $contentContext->getNodesOnPath($contentContext->getCurrentSiteNode(), $contentContext->getCurrentNode());

		if ($givenSiteLevel > 0 && isset($breadcrumbNodes[$givenSiteLevel - 1])) {
			$parentNode = $breadcrumbNodes[$givenSiteLevel - 1];
		} elseif ($givenSiteLevel <= 0) {
			$currentSiteLevel = count($breadcrumbNodes) - 1;
			if ($currentSiteLevel + $givenSiteLevel < 1) {
				$parentNode = $breadcrumbNodes[0];
			} else {
				$parentNode = $breadcrumbNodes[$currentSiteLevel + $givenSiteLevel - 1];
			}
		}
		return $parentNode;
	}
}
?>