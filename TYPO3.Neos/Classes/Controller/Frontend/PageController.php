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
	 * @var \F3\TYPO3\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext 
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectContentContext(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$this->contentContext = $contentContext;
	}

	/**
	 * Shows the page specified in the "page" argument.
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Page $page The page to show
	 * @param string $type The type for identifying the TypoScript page object
	 * @return string View output for the specified page
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction(\F3\TYPO3\Domain\Model\Content\Page $page, $type = 'default') {
		$typoScriptService = $this->contentContext->getTypoScriptService();
		$typoScriptObjectTree = $typoScriptService->getMergedTypoScriptObjectTree($this->contentContext->getNodePath());
		if ($typoScriptObjectTree === NULL || count($typoScriptObjectTree) === 0) {
			throw new \F3\TYPO3\Controller\Exception\NoTypoScriptConfigurationException('No TypoScript template was found for the current page context.', 1255513200);
		}

		foreach ($typoScriptObjectTree as $firstLevelTypoScriptObject) {
			if ($firstLevelTypoScriptObject instanceof \F3\TYPO3\TypoScript\Page && $firstLevelTypoScriptObject->getType() === $type) {
				$pageTypoScriptObject = $firstLevelTypoScriptObject;
				break;
			}
		}

		if (!isset($pageTypoScriptObject)) {
			throw new \F3\TYPO3\Controller\Exception\NoTypoScriptPageObjectException('No TypoScript Page object with type "' . $type . '" was found in the current TypoScript configuration.', 1255513201);
		}

		$renderingContext = $this->objectManager->create('F3\TypoScript\RenderingContext');
		$renderingContext->setControllerContext($this->controllerContext);
		$renderingContext->setContentContext($this->contentContext);

		$pageTypoScriptObject->setModel($page);
		$pageTypoScriptObject->setRenderingContext($renderingContext);
     	return $pageTypoScriptObject->render();
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