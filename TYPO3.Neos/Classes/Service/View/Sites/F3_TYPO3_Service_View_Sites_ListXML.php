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
 * XML view for the Sites List action
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ListXML extends F3::FLOW3::MVC::View::AbstractView {

	/**
	 * @var array An array of sites
	 */
	public $sites = array();

	/**
	 * Renders this list view
	 *
	 * @return string The rendered XML output
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		$sitesArray = array();

		$dom = new DOMDocument ('1.0', 'utf-8');
		$dom->formatOutput = TRUE;

		$domSites = $dom->appendChild(new DOMElement('sites'));
		foreach ($this->sites as $site) {
			$domSite = $domSites->appendChild(new DOMElement('site'));
			$domSite->appendChild(new DOMAttr('identifier', $site->getIdentifier()));
			$domSite->appendChild(new DOMElement('name', $site->getName()));
		}
		return $dom->saveXML();
	}
}
?>