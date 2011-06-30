<?php
namespace TYPO3\TYPO3\Controller;

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
 * Error Controller
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope singleton
 */
class ErrorController extends \TYPO3\FLOW3\MVC\Controller\ActionController implements \TYPO3\FLOW3\MVC\Controller\NotFoundControllerInterface {

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array('TYPO3\FLOW3\MVC\Web\Request', 'TYPO3\FLOW3\MVC\Cli\Request');

	/**
	 * @var \TYPO3\FLOW3\MVC\Controller\Exception
	 */
	protected $exception;

	/**
	 * Sets the controller exception
	 *
	 * @param \TYPO3\FLOW3\MVC\Controller\Exception $exception
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setException(\TYPO3\FLOW3\MVC\Controller\Exception $exception) {
		$this->exception = $exception;
	}

	/**
	 * Default action of this controller.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function indexAction() {
		if ($this->exception !== NULL) {
			$this->view->assign('errorMessage', $this->exception->getMessage());
		}
		switch (get_class($this->request)) {
			case 'TYPO3\FLOW3\MVC\Web\Request' :
				$pathWithoutFormat = substr($this->request->getRequestUri()->getPath(), 0 , strrpos($this->request->getRequestUri()->getPath(), '.'));
				preg_match(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::MATCH_PATTERN_CONTEXTPATH, $pathWithoutFormat, $matches);
				if (isset($matches['WorkspaceName'])) {
					$uri = $this->request->getBaseUri() . '@' . $matches['WorkspaceName'];
				} elseif ($this->securityContext->getParty() instanceof \TYPO3\TYPO3\Domain\Model\User) {
					$uri = $this->request->getBaseUri() . '@' . $this->securityContext->getParty()->getPreferences()->get('context.workspace');
				} else {
					$uri = $this->request->getBaseUri();
				}
				$this->view->assign('pageTitle', '404 Not Found');
				$this->view->assign('errorTitle', 'Page Not Found');
				$this->view->assign('errorDescription', 'Sorry, we could not find any page at this URL.<br />Please visit the <a href="' . $uri . '">homepage</a> to get back on the path.');
				$this->response->setStatus(404);
				break;
			default :
				return "\n404 Not Found\n\nNo controller could be resolved which would match your request.\n";
		}
	}

	/**
	 * Catch all action forwarding to the indexAction.
	 *
	 * @param string $methodName Original method name
	 * @param array $arguments Arguments
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveView() {
		$view = new $this->defaultViewObjectName();
		$view->setTemplatePathAndFilename('resource://TYPO3.TYPO3/Private/Templates/Error/Index.html');
		$view->setControllerContext($this->controllerContext);
		return $view;
	}

}
?>