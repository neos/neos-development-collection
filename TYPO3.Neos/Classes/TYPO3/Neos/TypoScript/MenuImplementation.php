<?php
namespace TYPO3\Neos\TypoScript;

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
use TYPO3\Neos\Domain\Service\NodeShortcutResolver;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\Exception as TypoScriptException;

/**
 * A TypoScript Menu object
 */
class MenuImplementation extends \TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation {

	/**
	 * Hard limit for the maximum number of levels supported by this menu
	 */
	const MAXIMUM_LEVELS_LIMIT = 100;

	const STATE_NORMAL = 'normal';
	const STATE_CURRENT = 'current';
	const STATE_ACTIVE = 'active';

	/**
	 * @Flow\Inject
	 * @var NodeShortcutResolver
	 */
	protected $nodeShortcutResolver;

	/**
	 * Internal cache for the startingPoint tsValue.
	 *
	 * @var NodeInterface
	 */
	protected $startingPoint;

	/**
	 * Internal cache for the currentLevel tsValue.
	 *
	 * @var integer
	 */
	protected $currentLevel;

	/**
	 * Internal cache for the lastLevel value.
	 *
	 * @var integer
	 */
	protected $lastLevel;

	/**
	 * Internal cache for the maximumLevels tsValue.
	 *
	 * @var integer
	 */
	protected $maximumLevels;

	/**
	 * An internal cache for the built menu items array.
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * @var NodeInterface
	 */
	protected $currentNode;

	/**
	 * Rootline of all nodes from the current node to the site root node, keys are depth of nodes.
	 *
	 * @var array<NodeInterface>
	 */
	protected $currentNodeRootline;

	/**
	 * Internal cache for the renderHiddenInIndex property.
	 *
	 * @var boolean
	 */
	protected $renderHiddenInIndex;

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
	 * @return integer
	 */
	public function getEntryLevel() {
		return $this->tsValue('entryLevel');
	}

	/**
	 * NodeType filter for nodes displayed in menu
	 *
	 * @return string
	 */
	public function getFilter() {
		$filter = $this->tsValue('filter');
		if ($filter === NULL) {
			$filter = 'TYPO3.Neos:Document';
		}
		return $filter;
	}

	/**
	 * Maximum number of levels which should be rendered in this menu.
	 *
	 * @return integer
	 */
	public function getMaximumLevels() {
		if ($this->maximumLevels === NULL) {
			$this->maximumLevels = $this->tsValue('maximumLevels');
			if ($this->maximumLevels > self::MAXIMUM_LEVELS_LIMIT) {
				$this->maximumLevels = self::MAXIMUM_LEVELS_LIMIT;
			}
		}

		return $this->maximumLevels;
	}

	/**
	 * Return evaluated lastLevel value.
	 *
	 * @return integer
	 */
	public function getLastLevel() {
		if ($this->lastLevel === NULL) {
			$this->lastLevel = $this->tsValue('lastLevel');
			if ($this->lastLevel > self::MAXIMUM_LEVELS_LIMIT) {
				$this->lastLevel = self::MAXIMUM_LEVELS_LIMIT;
			}
		}

		return $this->lastLevel;
	}

	/**
	 * @return NodeInterface
	 */
	public function getStartingPoint() {
		if ($this->startingPoint === NULL) {
			$this->startingPoint = $this->tsValue('startingPoint');
		}

		return $this->startingPoint;
	}

	/**
	 * Should nodes that have "hiddenInIndex" set still be visible in this menu.
	 *
	 * @return boolean
	 */
	public function getRenderHiddenInIndex() {
		if ($this->renderHiddenInIndex === NULL) {
			$this->renderHiddenInIndex = (boolean)$this->tsValue('renderHiddenInIndex');
		}

		return $this->renderHiddenInIndex;
	}

	/**
	 * @return array
	 */
	public function getItemCollection() {
		return $this->tsValue('itemCollection');
	}

	/**
	 * @return array
	 */
	public function getItems() {
		if ($this->items === NULL) {
			$typoScriptContext = $this->tsRuntime->getCurrentContext();
			$this->currentNode = isset($typoScriptContext['activeNode']) ? $typoScriptContext['activeNode'] : $typoScriptContext['documentNode'];
			$this->currentLevel = 1;
			$this->items = $this->buildItems();
		}

		return $this->items;
	}

	/**
	 * Builds the array of menu items containing those items which match the
	 * configuration set for this Menu object.
	 *
	 * @throws TypoScriptException
	 * @return array An array of menu items and further information
	 */
	protected function buildItems() {
		$items = array();

		if ($this->getItemCollection() !== NULL) {
			$menuLevelCollection = $this->getItemCollection();
		} else {
			$entryParentNode = $this->findMenuStartingPoint();
			if ($entryParentNode === NULL) {
				return $items;
			}
			$menuLevelCollection = $entryParentNode->getChildNodes($this->getFilter());
		}

		$items = $this->buildMenuLevelRecursive($menuLevelCollection);

		return $items;
	}

	/**
	 * @param array $menuLevelCollection
	 * @return array
	 */
	protected function buildMenuLevelRecursive(array $menuLevelCollection) {
		$items = array();
		foreach ($menuLevelCollection as $currentNode) {
			$item = $this->buildMenuItemRecursive($currentNode);
			if ($item === NULL) {
				continue;
			}

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Prepare the menu item with state and sub items if this isn't the last menu level.
	 *
	 * @param NodeInterface $currentNode
	 * @return array
	 */
	protected function buildMenuItemRecursive(NodeInterface $currentNode) {
		if ($currentNode->isVisible() === FALSE || ($this->getRenderHiddenInIndex() === FALSE && $currentNode->isHiddenInIndex() === TRUE) || $currentNode->isAccessible() === FALSE) {
			return NULL;
		}

		$possibleFinalNode = $this->nodeShortcutResolver->resolveShortcutTarget($currentNode);
		if (is_string($possibleFinalNode)) {
			$possibleFinalNode = $currentNode;
		} elseif ($possibleFinalNode === NULL || $possibleFinalNode->isAccessible() === FALSE || $possibleFinalNode->isVisible() === FALSE) {
			return NULL;
		} elseif (!$possibleFinalNode instanceof NodeInterface) {
			// Shortcut resolved to an unsupported type.
			return NULL;
		}

		$item = array(
			'node' => $possibleFinalNode,
			'originalNode' => $currentNode,
			'state' => self::STATE_NORMAL,
			'label' => $currentNode->getLabel()
		);

		$item['state'] = $this->calculateItemState($currentNode);
		if (!$this->isOnLastLevelOfMenu($currentNode)) {
			$this->currentLevel++;
			$item['subItems'] = $this->buildMenuLevelRecursive($currentNode->getChildNodes($this->getFilter()));
			$this->currentLevel--;
		}

		return $item;
	}

	/**
	 * Calculates the state of the given menu item (node) depending on the currentNode.
	 *
	 * @param NodeInterface $node
	 * @return string
	 */
	protected function calculateItemState(NodeInterface $node) {
		if ($node === $this->currentNode) {
			return self::STATE_CURRENT;
		}

		if ($node !== $this->currentNode->getContext()->getCurrentSiteNode() && in_array($node, $this->getCurrentNodeRootline())) {
			return self::STATE_ACTIVE;
		}

		return self::STATE_NORMAL;
	}

	/**
	 * Get the rootline from the current node up to the site node.
	 *
	 * @return array
	 */
	protected function getCurrentNodeRootline() {
		if ($this->currentNodeRootline === NULL) {
			$siteNode = $this->currentNode->getContext()->getCurrentSiteNode();
			$this->currentNodeRootline = array(
				$this->getNodeLevelInSite($this->currentNode) => $this->currentNode
			);
			$parentNode = $this->currentNode;
			while ($parentNode !== $siteNode && $parentNode->getParent() !== NULL) {
				$parentNode = $parentNode->getParent();
				$this->currentNodeRootline[$this->getNodeLevelInSite($parentNode)] = $parentNode;
			}

			krsort($this->currentNodeRootline);
		}

		return $this->currentNodeRootline;
	}

	/**
	 * Find the starting point for this menu. depending on given startingPoint
	 * If startingPoint is given, this is taken as starting point for this menu level,
	 * as a fallback the TypoScript context variable node is used.
	 *
	 * If entryLevel is configured this will be taken into account as well.
	 *
	 * @return NodeInterface
	 * @throws \TYPO3\TypoScript\Exception
	 */
	protected function findMenuStartingPoint() {
		$typoScriptContext = $this->tsRuntime->getCurrentContext();
		$startingPoint = $this->getStartingPoint();

		if (!isset($typoScriptContext['node']) && !$startingPoint) {
			throw new TypoScriptException('You must either set a "startingPoint" for the menu or "node" must be set in the TypoScript context.', 1369596980);
		}
		$startingPoint = $startingPoint ? : $typoScriptContext['node'];
		$entryParentNode = $this->findParentNodeInBreadcrumbPathByLevel($this->getEntryLevel(), $startingPoint);

		return $entryParentNode;
	}

	/**
	 * Checks if the given menuItem is on the last level for this menu, either defined by maximumLevels or lastLevels.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $menuItemNode
	 * @return boolean
	 */
	protected function isOnLastLevelOfMenu(NodeInterface $menuItemNode) {
		if ($this->currentLevel >= $this->getMaximumLevels()) {
			return TRUE;
		}

		if (($this->getLastLevel() !== NULL)) {
			if ($this->getNodeLevelInSite($menuItemNode) >= $this->calculateNodeDepthFromRelativeLevel($this->getLastLevel(), $this->currentNode)) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Finds the node in the current breadcrumb path between current site node and
	 * current node whose level matches the specified entry level.
	 *
	 * @param integer $givenSiteLevel The site level child nodes of the to be found parent node should have. See $this->entryLevel for possible values.
	 * @param NodeInterface $startingPoint
	 * @return NodeInterface The parent node of the node at the specified level or NULL if none was found
	 */
	protected function findParentNodeInBreadcrumbPathByLevel($givenSiteLevel, NodeInterface $startingPoint) {
		$parentNode = NULL;
		if ($givenSiteLevel === 0) {
			return $startingPoint;
		}

		$absoluteDepth = $this->calculateNodeDepthFromRelativeLevel($givenSiteLevel, $startingPoint);
		if (($absoluteDepth - 1) > $this->getNodeLevelInSite($startingPoint)) {
			return NULL;
		}

		$currentSiteNode = $this->currentNode->getContext()->getCurrentSiteNode();
		$breadcrumbNodes = $currentSiteNode->getContext()->getNodesOnPath($currentSiteNode, $startingPoint);

		if (isset($breadcrumbNodes[$absoluteDepth - 1])) {
			$parentNode = $breadcrumbNodes[$absoluteDepth - 1];
		}

		return $parentNode;
	}

	/**
	 * Calculates an absolute depth value for a relative level given.
	 *
	 * @param integer $relativeLevel
	 * @param NodeInterface $referenceNode
	 * @return integer
	 */
	protected function calculateNodeDepthFromRelativeLevel($relativeLevel, NodeInterface $referenceNode) {
		if ($relativeLevel > 0) {
			$depth = $relativeLevel;
		} else {
			$currentSiteDepth = $this->getNodeLevelInSite($referenceNode);
			if ($currentSiteDepth + $relativeLevel < 1) {
				$depth = 1;
			} else {
				$depth = $currentSiteDepth + $relativeLevel + 1;
			}
		}

		return $depth;
	}

	/**
	 * Node Level relative to site root node.
	 * 0 = Site root node
	 *
	 * @param NodeInterface $node
	 * @return integer
	 */
	protected function getNodeLevelInSite(NodeInterface $node) {
		$siteNode = $this->currentNode->getContext()->getCurrentSiteNode();
		return $node->getDepth() - $siteNode->getDepth();
	}

}