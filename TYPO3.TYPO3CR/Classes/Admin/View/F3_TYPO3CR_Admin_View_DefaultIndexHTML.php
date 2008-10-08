<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::Admin::View;

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
class DefaultIndexHTML extends F3::FLOW3::MVC::View::AbstractView {

	/**
	 * Renders the Admin viewport
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function render() {
		$template = $this->resourceManager->getResource('file://TYPO3CR/HTML/View_Admin_Viewport.html')->getContent();
		return $template;
		return str_replace('###BASEURI###', $this->request->getBaseURI(), $template);
	}

}
?>