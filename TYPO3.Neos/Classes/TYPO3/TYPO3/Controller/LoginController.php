<?php
namespace TYPO3\TYPO3\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\ExtJS\Annotations\ExtDirect;

/**
 * A controller which allows for logging into the backend
 *
 * @Flow\Scope("singleton")
 */
class LoginController extends \TYPO3\Flow\Security\Authentication\Controller\AbstractAuthenticationController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * Select special views according to format
	 *
	 * @return void
	 */
	protected function initializeAction() {
		switch ($this->request->getFormat()) {
			case 'extdirect' :
				$this->defaultViewObjectName = 'TYPO3\ExtJS\ExtDirect\View';
				$this->errorMethodName = 'extErrorAction';
				break;
			case 'json' :
				$this->defaultViewObjectName = 'TYPO3\Flow\Mvc\View\JsonView';
				break;
		}
	}

	/**
	 * Default action, displays the login screen
	 *
	 * @param string $username Optional: A username to prefill into the username field
	 * @return void
	 */
	public function indexAction($username = NULL) {
		$this->view->assign('username', $username);
		$this->view->assign('hostname', $this->request->getHttpRequest()->getBaseUri()->getHost());
		$this->view->assign('date', new \DateTime());
		$this->view->assign('welcomeMessage', 'Please enter your username and password in order to proceed.');

		$version = $this->objectManager->get('TYPO3\Flow\Package\PackageManagerInterface')->getPackage('TYPO3.TYPO3')->getPackageMetaData()->getVersion();
		$this->view->assign('version', $version);
	}

	/**
	 * Is called if authentication failed.
	 *
	 * @param \TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception The exception thrown while the authentication process
	 * @return void
	 */
	protected function onAuthenticationFailure(\TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception = NULL) {
		$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Error('The entered username or password was wrong.', ($exception === NULL ? 1347016771 : $exception->getCode())));
		$this->redirect('index');
	}

	/**
	 * Is called if authentication was successful.
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $originalRequest The request that was intercepted by the security framework, NULL if there was none
	 * @return string
	 */
	public function onAuthenticationSuccess(\TYPO3\Flow\Mvc\ActionRequest $originalRequest = NULL) {
		if ($originalRequest !== NULL) {
			$this->redirectToRequest($originalRequest);
		}
		$this->redirect('index', 'Backend\Backend');
	}

	/**
	 * Shows some information about the currently logged in account
	 *
	 * @return string
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
				return $this->view->render();
			default :
				return 'Hello ' . $person->getName()->getFirstName() . '. You are currently logged in with the account ' . $this->securityContext->getAccount()->getAccountIdentifier() . '.';
		}
	}

	/**
	 * Logs out a - possibly - currently logged in account.
	 *
	 * @return void
	 * @ExtDirect
	 */
	public function logoutAction() {
		parent::logoutAction();

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
				$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Notice('Successfully logged out.', 1318421560));
				$this->redirect('index');
		}
	}

	/**
	 * A preliminary error action for handling validation errors
	 * by assigning them to the ExtDirect View that takes care of
	 * converting them.
	 *
	 * @return void
	 */
	public function extErrorAction() {
		$this->view->assignErrors($this->arguments->getValidationResults()->getFlattenedErrors());
	}

}

?>