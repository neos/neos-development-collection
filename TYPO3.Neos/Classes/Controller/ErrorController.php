<?php
namespace TYPO3\TYPO3\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Error Controller
 *
 * @FLOW3\Scope("singleton")
 */
class ErrorController extends \TYPO3\FLOW3\Mvc\Controller\ActionController implements \TYPO3\FLOW3\Mvc\Controller\NotFoundControllerInterface {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Controller\Exception
	 */
	protected $exception;

	/**
	 * Sets the controller exception
	 *
	 * @param \TYPO3\FLOW3\Mvc\Controller\Exception $exception
	 * @return void
	 */
	public function setException(\TYPO3\FLOW3\Mvc\Controller\Exception $exception) {
		$this->exception = $exception;
	}

	/**
	 * Default action of this controller.
	 *
	 * @return void
	 */
	public function indexAction() {
		if ($this->exception !== NULL) {
			$this->view->assign('errorMessage', $this->exception->getMessage());
		}
		$httpRequest = $this->request->getHttpRequest();
		$uriPath = $httpRequest->getUri()->getPath();
		$uriPathWithoutFormat = substr($uriPath, 0, strrpos($uriPath, '.'));

		preg_match(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::MATCH_PATTERN_CONTEXTPATH, $uriPathWithoutFormat, $matches);
		if (isset($matches['WorkspaceName'])) {
			$uri = $httpRequest->getBaseUri() . '@' . $matches['WorkspaceName'];
		} elseif ($this->securityContext->getParty() instanceof \TYPO3\TYPO3\Domain\Model\User) {
			$uri = $httpRequest->getBaseUri() . '@' . $this->securityContext->getParty()->getPreferences()->get('context.workspace');
		} else {
			$uri = $httpRequest->getBaseUri();
		}
		$this->view->assign('pageTitle', '404 Not Found');
		$this->view->assign('errorTitle', 'Page Not Found');
		$this->view->assign('errorDescription', 'Sorry, we could not find any page at this URL.<br />Please visit the <a href="' . $uri . '">homepage</a> to get back on the path.');
		$this->response->setStatus(404);
	}

	/**
	 * Catch all action forwarding to the indexAction.
	 *
	 * @param string $methodName Original method name
	 * @param array $arguments Arguments
	 */
	public function __call($methodName, array $arguments) {
		if (substr($methodName, -6, 6) !== 'Action') {
			trigger_error('Tried to call unknown method "' . $methodName . '".', \E_USER_ERROR);
		}
		$this->forward('index');
	}

	/**
	 * Prepares a view for the current action and stores it in $this->view.
	 *
	 * @return \TYPO3\Fluid\View\ViewInterface the resolved view
	 */
	protected function resolveView() {
		$view = new $this->defaultViewObjectName();
		$view->setTemplatePathAndFilename('resource://TYPO3.TYPO3/Private/Templates/Error/Index.html');
		$view->setControllerContext($this->controllerContext);
		return $view;
	}

}
?>