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
 * The TYPO3 Backend controller
 *
 * @package   TYPO3
 * @version   $Id:T3_TYPO3_Controller_Page.php 262 2007-07-13 10:51:44Z robert $
 * @copyright Copyright belongs to the respective authorst
 * @license   http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_TYPO3_Controller_Backend extends T3_FLOW3_MVC_Controller_ActionController {

	protected $supportedRequestTypes = array('T3_FLOW3_MVC_Web_Request');

	/**
	 * The default action
	 *
	 * @param  T3_FLOW3_MVC_Web_Request $request: The request to process
	 * @param  T3_FLOW3_MVC_Response $response: The response
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function defaultAction() {
		$this->response->setContent('TYPO3 Backend');
	}
}
?>