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
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * Select special views according to format
	 *
	 * @return void
	 */
	protected function initializeAction() {
		switch ($this->request->getFormat()) {
			case 'json':
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
		$this->view->assignMultiple(array(
			'username' => $username,
			'welcomeMessage' => 'Please enter your username and password in order to proceed.'
		));
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
	 * @return void
	 */
	public function onAuthenticationSuccess(\TYPO3\Flow\Mvc\ActionRequest $originalRequest = NULL) {
		if ($originalRequest !== NULL) {
			$this->redirectToRequest($originalRequest);
		}
		$this->redirect('index', 'Backend\Backend');
	}

	/**
	 * Logs out a - possibly - currently logged in account.
	 *
	 * @return void
	 */
	public function logoutAction() {
		parent::logoutAction();

		switch ($this->request->getFormat()) {
			case 'json':
				$this->view->assign('value',
					array(
						'success' => TRUE
					)
				);
				break;
			default:
				if (isset($_COOKIE['Neos_lastVisitedUri'])) {
					$contentContext = new \TYPO3\Neos\Domain\Service\ContentContext('live');
					$this->nodeRepository->setContext($contentContext);

					$redirectUri = $_COOKIE['Neos_lastVisitedUri'];
					$appendHtml = !strpos($redirectUri, '.html') ? FALSE : TRUE;
					if (!strpos($redirectUri, '@')) {
						$redirectUri = str_replace('.html', '', $redirectUri);
					} else {
						$redirectUri = substr($redirectUri, 0, strpos($redirectUri, '@'));
					}
					$urlParts = parse_url($redirectUri);
					if ($urlParts['path'] && is_object($contentContext->getCurrentSiteNode()->getNode(substr($urlParts['path'], 1)))) {
						$redirectUri .= $appendHtml === TRUE ? '.html' : '';
						$this->redirectToUri($redirectUri);
					}
				}
				$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Notice('Successfully logged out.', 1318421560));
				$this->redirect('index');
		}
	}

}
?>