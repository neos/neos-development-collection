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
		$items = array();
		$currentNodesPath = $contentContext->getCurrentNodePath();

		$dummy = '';
		$entryParentNode = $this->findParentNodeByLevel($this->entryLevel, $dummy, $contentContext);
		if ($entryParentNode === NULL) {
			return array();
		}
		$lastParentNode = $this->findParentNodeByLevel($this->lastLevel, $dummy, $contentContext);

		$nodePath = '';
		$addItems = ($entryParentNode instanceof \F3\TYPO3\Domain\Model\Structure\Site) ? $this->maximumLevels : 0;

		foreach ($contentContext->getNodeService()->getNodesOnPath($currentNodesPath) as $node) {
			$nodePath .= '/' . $node->getNodeName();
			if ($addItems > 0) {
				$items[] = array(
					 'label' => $node->getContent($contentContext)->getTitle(),
					 'nodePath' => $nodePath,
				);
			}
			$addItems --;
			if ($node === $entryParentNode) {
				$addItems = $this->maximumLevels;
			}
			if ($node === $lastParentNode) {
				$addItems = 1;
			}
		}
		return $items;
	}

}

?>