<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id:F3_TYPO3_View_Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * JSON view for the Sites List action
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3_TYPO3_View_Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_Service_View_Sites_ListJSON extends F3_FLOW3_MVC_View_AbstractView {

	/**
	 * @var array An array of sites
	 */
	protected $sites;

	/**
	 * Sets the sites (model) for this view
	 *
	 * @param array $sites An array of F3_TYPO3_Domain_Model_Site objects
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSites(array $sites) {
		$this->sites = $sites;
	}

	/**
	 * Renders this list view
	 *
	 * @return string The rendered JSON output
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		$sitesArray = array();
		foreach ($this->sites as $site) {
			$info = key_exists('test', $_POST) ? $_POST['test'] : '';

			$pageIdentifiers = array();
			foreach ($site->getPages() as $page) {
				$pageIdentifiers[] = $page->getIdentifier();
			}

			$sitesArray[] = array(
				'id' => $site->getIdentifier(),
				'text' => $site->getName() . $info,
				'nodeType' => 'F3_TYPO3_Domain_Model_Site',
				'pages' => $pageIdentifiers,
				'icon' => 'Resources/Web/TYPO3/Public/Backend/Media/Icons/Site.png'
			);
		}
		return json_encode($sitesArray);
	}
}
?>