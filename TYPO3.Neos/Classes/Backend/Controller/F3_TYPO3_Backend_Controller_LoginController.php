<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Backend::Controller;

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
 * A controller which allows for loggin into the backend
 *
 * @package TYPO3
 * @subpackage Backend
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class LoginController extends F3::FLOW3::MVC::Controller::ActionController {

	/**
	 * Default action for this controller
	 *
	 * @return string Some login form
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function indexAction() {
		$output = '
			<form action="" method="post">
	 			User: <input type="text" name="F3::FLOW3::Security::Authentication::Token::UsernamePassword::username" />
				Password <input type="password" name="F3::FLOW3::Security::Authentication::Token::UsernamePassword::password" />
				<input type="submit" value="Login" />
			</form>
		';
		return $output;
	}
}

?>