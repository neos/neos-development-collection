<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Service\View\Sites;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3
 * @subpackage Service
 * @version $Id:\F3\TYPO3\View\Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * JSON view for the Sites List action
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:\F3\TYPO3\View\Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ListJSON extends \F3\FLOW3\MVC\View\AbstractView {

	/**
	 * @var array An array of sites
	 */
	public $sites = array();

	/**
	 * Renders this list view
	 *
	 * @return string The rendered JSON output
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		$sitesArray = array();
		foreach ($this->sites as $site) {
			$sitesArray[] = array(
				'id' => $site->getId(),
				'name' => $site->getName(),
				'rootStructureNode' => $site->getRootStructureNode()->getId()
			);
		}
		return json_encode($sitesArray);
	}
}
?>