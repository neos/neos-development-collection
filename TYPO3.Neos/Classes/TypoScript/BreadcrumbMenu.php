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
 * @version $Id: Text.php 4448 2010-06-07 13:24:31Z robert $
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class BreadcrumbMenu extends \F3\TYPO3\TypoScript\Menu {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3/Private/TypoScript/Templates/BreadcrumbMenu.html';

	/**
	 * The last navigation level which should be rendered.
	 *
	 * 0 = top level of the site
	 * 1 = first sub level (2nd level)
	 * 2 = second sub level (3rd level)
	 * ...
	 *
	 * -1 = last level
	 * -2 = level above the last level
	 * ...
	 *
	 * @var integer
	 */
	protected $lastLevel = -2;

	/**
	 * @var array
	 */
	protected $items;

	/**
	 * Returns the menu items according to the defined settings.
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getItems() {
		$items = array();
		$nodes = $this->renderingContext->getContentContext()->getNodeService()->getNodesOnPath($this->renderingContext->getContentContext()->getCurrentSite(), $this->renderingContext->getContentContext()->getNodePath());
		foreach ($nodes as $node) {
			$items[] = array('label' => $node->getNodeName());
		}
		return $items;
	}

}
?>