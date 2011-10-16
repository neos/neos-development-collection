<?php
namespace TYPO3\TYPO3\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\ExtJS\Annotations\ExtDirect;

/**
 * A controller which allows for logging into the backend
 *
 * @FLOW3\Scope("singleton")
 */
class LoginController extends \TYPO3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Select special views according to format
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function initializeAction() {
		switch ($this->request->getFormat()) {
			case 'extdirect' :
				$this->defaultViewObjectName = 'TYPO3\ExtJS\ExtDirect\View';
				$this->errorMethodName = 'extErrorAction';
				break;
			case 'json' :
				$this->defaultViewObjectName = 'TYPO3\FLOW3\MVC\View\JsonView';
				break;
		}
	}

	/**
	 * Default action, displays the login screen
	 *
	 * @param string $username Optional: A username to prefill into the username field
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function indexAction($username = NULL) {
		$this->view->assign('username', $username);

		$version = $this->packageManager->getPackage('TYPO3.TYPO3')->getPackageMetaData()->getVersion();
		$this->view->assign('version', $version);
	}

	/**
	 * Authenticates an account by invoking the Provider based Authentication Manager.
	 *
	 * On successful authentication redirects to the backend, otherwise returns
	 * to the login screen.
	 *
	 * Note: You need to send the username and password these two POST parameters:
	 *       __authentication[TYPO3][FLOW3][Security][Authentication][Token][UsernamePassword][username]
	 *   and __authentication[TYPO3][FLOW3][Security][Authentication][Token][UsernamePassword][password]
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function authenticateAction() {
		$authenticated = FALSE;
		try {
			$this->authenticationManager->authenticate();
			$authenticated = TRUE;
		} catch (\TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException $exception) {
		}

		if ($authenticated) {
			$this->redirect('index', 'Backend\Backend');
		} else {
			$this->flashMessageContainer->addMessage(new \TYPO3\FLOW3\Error\Error('Wrong username or password.'));
			$this->redirect('index');
		}
	}


	/**
	 * Shows some information about the currently logged in account
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @ExtDirect
	 */
	public function showAction() {
		$person = $this->securityContext->getParty();

		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->setConfiguration(
					array(
						'value' => array(
							'data' => array(
								'_descend' => array('name' => array())
							)
						)
					)
				);
				$this->view->assign('value',
					array(
						'data' => $person,
						'success' => TRUE
					)
				);
				break;
			default :
				return 'Hello ' . $person->getName()->getFirstName() . '. You are currently logged in with the account '. $this->securityContext->getAccount()->getAccountIdentifier() . '.';
		}
	}

	/**
	 * Logs out a - possibly - currently logged in account.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @ExtDirect
	 */
	public function logoutAction() {
		$this->authenticationManager->logout();

		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->assign('value',
					array(
						'success' => TRUE
					)
				);
				break;
			default :
				$this->flashMessageContainer->addMessage(new \TYPO3\FLOW3\Error\Notice('Successfully logged out.', 1318421560));
				$this->redirect('index');
		}
	}

	/**
	 * A preliminary error action for handling validation errors
	 * by assigning them to the ExtDirect View that takes care of
	 * converting them.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function extErrorAction() {
		$this->view->assignErrors($this->arguments->getValidationResults()->getFlattenedErrors());
	}

}

?>