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
 * A TypoScript Breadcrumb Menu object
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class BreadcrumbMenu extends \F3\TYPO3\TypoScript\Menu {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3/Private/TypoScript/Templates/BreadcrumbMenu.html';

	/**
	 * Maximum number of levels which should be rendered in this menu.
	 *
	 * @var integer
	 */
	protected $maximumLevels = self::MAXIMUM_LEVELS_LIMIT;

	/**
	 * Builds the array of menu items containing those items which match the
	 * configuration set for this Breadcrumbmenu object.
	 *
	 * @return array An array of menu items and further information
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildItems(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {

		$breadcrumbNodes = $contentContext->getNodesOnPath($contentContext->getCurrentSiteNode(), $contentContext->getCurrentNode());
		$currentSiteLevel = count($breadcrumbNodes) - 1;
		$normalizedEntryLevel = FALSE;
		$normalizedLastLevel = $currentSiteLevel;

		if ($this->entryLevel > 0 && isset($breadcrumbNodes[$this->entryLevel])) {
			$normalizedEntryLevel = $this->entryLevel;
		} elseif ($this->entryLevel <= 0) {
			if ($currentSiteLevel + $this->entryLevel < 1) {
				$normalizedEntryLevel = 1;
			} else {
				$normalizedEntryLevel = $currentSiteLevel + $this->entryLevel;
			}
		}

		if ($normalizedEntryLevel === FALSE) {
			return array();
		}

		if ($this->lastLevel > 0 && isset($breadcrumbNodes[$this->lastLevel])) {
			$normalizedLastLevel = $this->lastLevel;
		} elseif ($this->lastLevel <= 0) {
			if ($currentSiteLevel + $this->lastLevel < 1) {
				$normalizedLastLevel = 1;
			} else {
				$normalizedLastLevel = $currentSiteLevel + $this->lastLevel;
			}
		}

		if ($normalizedLastLevel < $normalizedEntryLevel) {
			$normalizedLastLevel = $normalizedEntryLevel;
		}

		$items = array();
		for ($i = $normalizedEntryLevel; $i <= $normalizedLastLevel; $i++) {
			$node = $breadcrumbNodes[$i];

			$item = array(
				 'label' => $node->getProperty('title'),
				 'node' => $node,
			);
			if ($node === $contentContext->getCurrentNode()) {
				$item['state'][self::STATE_ACTIVE] = TRUE;
			}

			$items[] = $item;
		}

		return $items;
	}
}

?>