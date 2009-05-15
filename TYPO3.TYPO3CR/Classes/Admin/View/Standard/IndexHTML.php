<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Admin\View\Standard;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class IndexHTML extends \F3\FLOW3\MVC\View\AbstractView {

	/**
	 * Renders the Admin viewport
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function render() {
		$template = $this->resourceManager->getResource('file://TYPO3CR/Public/HTML/View_Admin_Viewport.html')->getContent();
		return str_replace('###BASEURI###', $this->controllerContext->getRequest()->getBaseURI(), $template);
	}

}
?>