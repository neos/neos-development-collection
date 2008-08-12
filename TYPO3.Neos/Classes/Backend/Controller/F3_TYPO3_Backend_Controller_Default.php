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
 * @version $Id:F3_TYPO3_Controller_Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * The TYPO3 Backend controller
 *
 * @package TYPO3
 * @version $Id:F3_TYPO3_Controller_Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_Backend_Controller_Default extends F3_FLOW3_MVC_Controller_ActionController {

	/**
	 * @var array Only Web Requests are supported
	 */
	protected $supportedRequestTypes = array('F3_FLOW3_MVC_Web_Request');

	/**
	 * Initializes this action controller
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeController() {
		$this->arguments->addNewArgument('section');
		$this->arguments->addNewArgument('module');
	}

	/**
	 * Default action of the backend controller.
	 * Forwards the request to the default module.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function defaultAction() {
		return $this->view->render();
	}
}
?>