<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Service::View::Sites;

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
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * JSON view for the Site Show action
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ShowJSON extends F3::FLOW3::MVC::View::AbstractView {

	/**
	 * @var F3::TYPO3::Domain::Model::Site
	 */
	protected $site;

	/**
	 * Sets the site (model) for this view
	 *
	 * @param array $sites An array of F3::TYPO3::Domain::Model::Site objects
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSite(F3::TYPO3::Domain::Model::Site $site) {
		$this->site = $site;
	}

	/**
	 * Renders this show view
	 *
	 * @return string The rendered JSON output
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		$pageIdentifiers = array();
		foreach ($this->site->getPages() as $page) {
			$pageIdentifiers[] = $page->getIdentifier();
		}

		$siteArray[] = array(
			'identifier' => $this->site->getIdentifier(),
			'name' => $this->site->getName(),
			'pages' => $pageIdentifiers
		);
		return json_encode($siteArray);
	}
}
?>