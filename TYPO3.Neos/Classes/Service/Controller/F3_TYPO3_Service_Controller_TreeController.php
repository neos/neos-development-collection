<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Service::Controller;

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
 * @version $Id:F3::TYPO3::Controller::Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * The "Tree" service
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3::TYPO3::Controller::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TreeController extends F3::FLOW3::MVC::Controller::RESTController {

	/**
	 * Initializes the arguments of this controller
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeArguments() {
		parent::initializeArguments();

		$this->arguments->addNewArgument('locale', 'Text');
	}

	/**
	 * A demo show action
	 *
	 * @return string Output of the list view
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction() {
		return $this->view->render();
	}
}
?>