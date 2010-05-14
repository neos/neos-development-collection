<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Controller\Frontend;

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
 * TYPO3's frontend page controller
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @inject
	 * @var \F3\TYPO3\Routing\PageRoutePartHandler
	 */
	protected $pageRoutePartHandler;

	/**
	 * @var \F3\TYPO3\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * Tasks to deal with before an action is called
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeAction() {
		$this->contentContext = $this->pageRoutePartHandler->getContentContext();
		if ($this->contentContext->getCurrentSite() === NULL) {
			throw new \F3\TYPO3\Controller\Exception\NoSite('No site has been defined or matched the current frontend context.', 1247043365);
		}
	}

	/**
	 * Shows the page specified in the "page" argument.
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Page $page The page to show
	 * @param string $type The type for identifying the TypoScript page object
	 * @return string View output for the specified page
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction(\F3\TYPO3\Domain\Model\Content\Page $page = NULL, $type = 'default') {
		if ($page === NULL) {
			return $this->pageNotFoundError();
		}

		$typoScriptService = $this->contentContext->getTypoScriptService();
		$typoScriptObjectTree = $typoScriptService->getMergedTypoScriptObjectTree($this->contentContext->getNodePath());
		if ($typoScriptObjectTree === NULL || count($typoScriptObjectTree) === 0) {
			throw new \F3\TYPO3\Controller\Exception\NoTypoScriptConfiguration('No TypoScript template was found for the current page context.', 1255513200);
		}

		foreach ($typoScriptObjectTree as $firstLevelTypoScriptObject) {
			if ($firstLevelTypoScriptObject instanceof \F3\TYPO3\TypoScript\Page && $firstLevelTypoScriptObject->getType() === $type) {
				$pageTypoScriptObject = $firstLevelTypoScriptObject;
			}
		}

		if (!isset($pageTypoScriptObject)) {
			throw new \F3\TYPO3\Controller\Exception\NoTypoScriptPageObject('No TypoScript Page object with type "' . $type . '" was found in the current TypoScript configuration.', 1255513201);
		}

		$pageTypoScriptObject->setModel($page);
		$renderingContext = $this->objectManager->create(
			'F3\TypoScript\RenderingContext', $this->controllerContext, $this->contentContext
		);
		return $pageTypoScriptObject->render($renderingContext);
	}

	/**
	 * @param string $message The error message
	 * @return void
	 */
	protected function pageNotFoundError() {
		$this->response->setStatus(404);
		$this->view = $this->objectManager->get('F3\TYPO3\View\Error\PageNotFoundView');
		$this->view->setControllerContext($this->controllerContext);
	}

	/**
	 * A dummy implementation, because this controller does not need its own view.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveView() {
	}
}
?>