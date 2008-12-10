<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Backend\View;

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
 * @subpackage Backend
 * @version $Id$
 */

/**
 * The TYPO3 Backend View
 *
 * @package TYPO3
 * @subpackage Backend
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DefaultIndexHTML extends \F3\Smarty\View {

	/**
	 * Initializes the view
	 *
	 * @return void
	 */
	protected function initializeView() {
		parent::initializeView();
		$this->setTemplateFileName($this->resourceManager->getResource('file://TYPO3/Private/Backend/HTML/Index.html')->getPathAndFileName());
	}

}

?>