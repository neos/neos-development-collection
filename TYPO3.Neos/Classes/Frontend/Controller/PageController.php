<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Frontend\Controller;

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
	 * @var \F3\TYPO3\Domain\Service\FrontendContentContext
	 */
	protected $contentContext;

	/**
	 * @var \F3\TYPO3\Domain\Model\Structure\Site
	 */
	protected $currentSite;

	/**
	 * Tasks to deal with before an action is called
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeAction() {
		$this->contentContext = $this->objectFactory->create('F3\TYPO3\Domain\Service\FrontendContentContext');
		$this->currentSite = $this->contentContext->getCurrentSite();
		if ($this->currentSite === NULL) {
			throw new \F3\TYPO3\Frontend\Exception\NoSite('No site has been defined or matched the current frontend context.', 1247043365);
		}
	}

	/**
	 * Show the root page, because no page was specified
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function indexAction() {
		$structureNode = $this->currentSite->getRootNode($this->contentContext);

		$configurations = $structureNode->getConfigurations();
		$configurations->rewind();
		$typoScriptTemplate = $configurations->current();

		$pageModel = $structureNode->getContent($this->contentContext);

		$typoScriptObjectTree = $typoScriptTemplate->getObjectTree();

		$pageTypoScriptObject = $typoScriptObjectTree['page'];
		$pageTypoScriptObject->setModel($pageModel);

		$this->view->setControllerContext($this->buildControllerContext());
		$pageTypoScriptObject->injectView($this->view);

		return $pageTypoScriptObject->getRenderedContent();
	}

	/**
	 * Shows the page specified in the "page" argument
	 *
	 * @param ...
	 * @return string View output for the specified page
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction($page) {
		return "<br />\nTYPO3 Frontend: show()";
	}

	/**
	 * Prepares the Fluid template view
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveView() {
		$view = $this->objectManager->getObject('F3\Fluid\View\TemplateView');
		$view->setControllerContext($this->buildControllerContext());
		return $view;
	}
}
?>