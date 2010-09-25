<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Controller;

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
 */
class ErrorController extends \F3\FLOW3\MVC\Controller\ActionController implements \F3\FLOW3\MVC\Controller\NotFoundControllerInterface {

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array('F3\FLOW3\MVC\Web\Request', 'F3\FLOW3\MVC\Cli\Request');

	/**
	 * @var \F3\FLOW3\MVC\Controller\Exception
	 */
	protected $exception;

	/**
	 * Sets the controller exception
	 *
	 * @param \F3\FLOW3\MVC\Controller\Exception $exception
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setException(\F3\FLOW3\MVC\Controller\Exception $exception) {
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
			case 'F3\FLOW3\MVC\Web\Request' :
				$this->view->assign('pageTitle', '404 Not Found');
				$this->view->assign('errorTitle', 'Page Not Found');
				$this->view->assign('errorDescription', 'Sorry, we could not find any page at this URL.<br />Please visit the <a href="/">homepage</a> to get back on the path.');
				$this->response->setStatus(404);
				break;
			default :
				return "\n404 Not Found\n\nNo controller could be resolved which would match your request.\n";
		}
	}

	/**
	 * Prepares a view for the current action and stores it in $this->view.
	 *
	 * @return \F3\Fluid\View\ViewInterface the resolved view
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveView() {
		$view = $this->objectManager->create($this->defaultViewObjectName);
		$view->setTemplatePathAndFilename('resource://TYPO3/Private/Templates/Error/Index.html');
		$view->setControllerContext($this->controllerContext);
		return $view;
	}

}
?>