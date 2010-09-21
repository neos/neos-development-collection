<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A TypoScript Menu object
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Menu extends \F3\TypoScript\AbstractContentObject {

	/**
	 * Hard limit for the maximum number of levels supported by this menu
	 */
	const MAXIMUM_LEVELS_LIMIT = 100;

	const STATE_ACTIVE = 'active';
	const STATE_INACTIVE = 'inactive';

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3/Private/TypoScript/Templates/Menu.html';

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array('items');

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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setEntryLevel($entryLevel) {
		$this->entryLevel = $entryLevel;
	}

	/**
	 * @return integer
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getEntryLevel() {
		return $this->entryLevel;
	}

	/**
	 * @param integer $maximumLevels
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setMaximumLevels($maximumLevels) {
		if ($maximumLevels > self::MAXIMUM_LEVELS_LIMIT) {
			$maximumLevels = self::MAXIMUM_LEVELS_LIMIT;
		}
		$this->maximumLevels = $maximumLevels;
	}

	/**
	 * @return integer
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMaximumLevels($maximumLevels) {
		return $this->maximumLevels;
	}

	/**
	 * @param integer $lastLevel
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setLastLevel($lastLevel) {
		if ($lastLevel > self::MAXIMUM_LEVELS_LIMIT) {
			$lastLevel = self::MAXIMUM_LEVELS_LIMIT;
		}
		$this->lastLevel = $lastLevel;
	}

	/**
	 * @return integer
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLastLevel() {
		return $this->lastLevel;
	}

	/**
	 * Returns the menu items according to the defined settings.
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getItems() {
		if ($this->items === NULL) {
			$this->items = $this->buildItems($this->renderingContext->getContentContext());
		}
     return $this->items;
   }

	/**
	 * Builds the array of menu items containing those items which match the
	 * configuration set for this Menu object.
	 *
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext
	 * @return array An array of menu items and further information
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildItems(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {
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
	 * @param \F3\TYPO3CR\Domain\Model\Node $entryParentNode The parent node whose children should be listed as items
	 * @param \F3\TYPO3CR\Domain\Model\Node $lastParentNode The last parent node whose children should be listed. NULL = no limit defined through lastLevel
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext $contentContext The current content context
	 * @param integer $currentLevel Level count for the recursion – don't use.
	 * @return array A nested array of menu item information
	 * @author Robert Lemke <robert@typo3.org>
	 * @see buildItems()
	 */
	private function buildRecursiveItemsArray(\F3\TYPO3CR\Domain\Model\Node $entryParentNode, $lastParentNode, \F3\TYPO3\Domain\Service\ContentContext $contentContext, $currentLevel = 1) {
		$items = array();
		foreach ($entryParentNode->getChildNodes('typo3:page') as $currentNode) {
			$item = array(
				 'label' => $currentNode->getProperty('title'),
				 'node' => $currentNode,
			);
			if ($currentNode === $contentContext->getCurrentNode()) {
				$item['state'][self::STATE_ACTIVE] = TRUE;
			}

			if ($currentLevel < $this->maximumLevels && $entryParentNode !== $lastParentNode) {
				$subItems = $this->buildRecursiveItemsArray($currentNode, $lastParentNode, $contentContext, $currentLevel + 1);
				if ($subItems !== array()) {
					$item['subItems'] = $subItems;
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
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext
	 * @return \F3\TYPO3CR\Domain\Model\Node The parent node of the node at the specified level or NULL if none was found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	private function findParentNodeInBreadcrumbPathByLevel($givenSiteLevel, \F3\TYPO3\Domain\Service\ContentContext $contentContext) {
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