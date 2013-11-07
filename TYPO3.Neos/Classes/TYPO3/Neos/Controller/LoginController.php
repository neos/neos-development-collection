<?php
namespace TYPO3\Neos\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Security\Authentication\Controller\AbstractAuthenticationController;
use TYPO3\Flow\Security\Exception\AuthenticationRequiredException;
use TYPO3\Flow\Session\SessionInterface;
use TYPO3\Neos\Service\BackendRedirectionService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A controller which allows for logging into the backend
 */
class LoginController extends AbstractAuthenticationController {

	/**
	 * @Flow\Inject
	 * @var SessionInterface
	 */
	protected $session;

	/**
	 * @Flow\Inject
	 * @var BackendRedirectionService
	 */
	protected $backendRedirectionService;

	/**
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array('json' => 'TYPO3\Flow\Mvc\View\JsonView');

	/**
	 * Default action, displays the login screen
	 *
	 * @param string $username Optional: A username to pre-fill into the username field
	 * @return void
	 */
	public function indexAction($username = NULL) {
		$this->view->assign('username', $username);
	}

	/**
	 * Is called if authentication failed.
	 *
	 * @param AuthenticationRequiredException $exception The exception thrown while the authentication process
	 * @return void
	 */
	protected function onAuthenticationFailure(AuthenticationRequiredException $exception = NULL) {
		$this->addFlashMessage('The entered username or password was wrong', 'Wrong credentials', Message::SEVERITY_ERROR, array(), ($exception === NULL ? 1347016771 : $exception->getCode()));
	}

	/**
	 * Is called if authentication was successful.
	 *
	 * @param ActionRequest $originalRequest The request that was intercepted by the security framework, NULL if there was none
	 * @return void
	 */
	public function onAuthenticationSuccess(ActionRequest $originalRequest = NULL) {
		if ($this->request->hasArgument('lastVisitedNode') && strlen($this->request->getArgument('lastVisitedNode')) > 0) {
			$this->session->putData('lastVisitedNode', $this->request->getArgument('lastVisitedNode'));
		}

		if ($originalRequest !== NULL) {
			// Redirect to the location that redirected to the login form because the user was nog logged in
			$this->redirectToRequest($originalRequest);
		}

		$this->redirect('index', 'Backend\Backend');
	}

	/**
	 * Logs out a - possibly - currently logged in account.
	 * The possible redirection URI is queried from the redirection service
	 * at first, before the actual logout takes place, and the session gets destroyed.
	 *
	 * @return void
	 */
	public function logoutAction() {
		$possibleRedirectionUri = $this->backendRedirectionService->getAfterLogoutRedirectionUri($this->request);
		parent::logoutAction();
		switch ($this->request->getFormat()) {
			case 'json':
				$this->view->assign('value', array('success' => TRUE));
			break;
			default:
				if ($possibleRedirectionUri !== NULL) {
					$this->redirectToUri($possibleRedirectionUri);
				}
				$this->addFlashMessage('Successfully logged out', 'Logged out', Message::SEVERITY_NOTICE, array(), 1318421560);
				$this->redirect('index');
		}
	}

	/**
	 * Disable the default error flash message
	 *
	 * @return boolean
	 */
	protected function getErrorFlashMessage() {
		return FALSE;
	}
}
