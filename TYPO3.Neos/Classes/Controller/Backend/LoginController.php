<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Controller\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A controller which allows for logging into the backend
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class LoginController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @inject
	 * @var \F3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * Default action, displays the login screen
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function indexAction() {
	}

	/**
	 * Authenticates an account by invoking the Provider based Authentication Manager.
	 *
	 * On successful authentication redirects to the backend, otherwise returns
	 * to the login screen.
	 *
	 * Note: You need to send the username and password these two POST parameters:
	 *       F3\FLOW3\Security\Authentication\Token\UsernamePassword::username
	 *   and F3\FLOW3\Security\Authentication\Token\UsernamePassword::password
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function authenticateAction() {
		$authenticated = FALSE;
		try {
			$this->authenticationManager->authenticate();
			$authenticated = TRUE;
		} catch (\F3\FLOW3\Security\Exception\AuthenticationRequiredException $exception) {
		}

		switch ($this->request->getFormat()) {
			case 'json' :
				if ($authenticated) {
					$response = array(
						'success' => TRUE,
						'redirectUri' => $this->uriBuilder
							->reset()
							->setCreateAbsoluteUri(TRUE)
							->uriFor('index', array(), 'Backend\Backend', 'TYPO3')
					);
				} else {
					$response = array(
						'success' => FALSE,
						'redirectUri' => $this->uriBuilder
							->reset()
							->setCreateAbsoluteUri(TRUE)
							->uriFor('index')
					);
				}
				return json_encode($response);
			default :
				if ($authenticated) {
					$this->redirect('index', 'Backend\Backend');
				} else {
					$this->flashMessageContainer->add('Wrong username or password.');
					$this->redirect('index');
				}
		}
	}

	/**
	 * Logs out a - possibly - currently logged in account.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logoutAction() {
		$this->authenticationManager->logout();

		switch ($this->request->getFormat()) {
			case 'json' :
				$response = array(
					'success' => TRUE
				);
				return json_encode($response);
			default :
				$this->flashMessageContainer->add('Successfully logged out.');
				$this->redirect('index');
		}
	}

}

?>