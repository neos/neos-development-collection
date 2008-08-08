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
 * @package TYPO3CR
 * @subpackage Admin
 * @version $Id$
 */

/**
 * Renders the full viewport for the standalone CR admin
 *
 * @package TYPO3CR
 * @subpackage Admin
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_View_Admin_Viewport extends F3_FLOW3_MVC_View_AbstractView {

	/**
	 * @var F3_FLOW3_MVC_Web_Request
	 */
	protected $request;

	/**
	 *
	 * @param unknown_type $request
	 */
	public function setRequest($request) {
		$this->request = $request;
	}

	/**
	 * Renders the Admin viewport
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function render() {
		$HTML = $this->resourceManager->getResource('file://TYPO3CR/HTML/View_Admin_Viewport.html')->getContent();
		$HTML = str_replace('###BASEURI###', $this->request->getBaseURI(), $HTML);
		return $HTML;
	}

}
?>