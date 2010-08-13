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
 * A generic controller for displaying and managing content objects
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ContentController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'F3\Fluid\View\TemplateView';

	/**
	 * @var \F3\TYPO3\Domain\Repository\Content\ContentRepository
	 */
	protected $contentRepository;

	/**
	 * @var \F3\TYPO3\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * @param \F3\TYPO3\Domain\Repository\Content\ContentRepository $contentRepository
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectContentRepository(\F3\TYPO3\Domain\Repository\Content\ContentRepository $contentRepository) {
		$this->contentRepository = $contentRepository;
	}

	/**
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectContentContext(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$this->contentContext = $contentContext;
	}

	/**
	 * Select special views according to format
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeAction() {
		switch ($this->request->getFormat()) {
			case 'extdirect' :
				$this->defaultViewObjectName = 'F3\ExtJS\ExtDirect\View';
				$this->errorMethodName = 'extErrorAction';
				break;
			case 'json' :
				$this->defaultViewObjectName = 'F3\FLOW3\MVC\View\JsonView';
				break;
		}
	}

	/**
	 * Shows the specified content
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\ContentInterface $content The content to show
	 * @param string $type The type as configured in the TypoScript for rendering the content
	 * @return string View output for the specified content
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function showAction(\F3\TYPO3\Domain\Model\Content\ContentInterface $content, $type = 'default') {
		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->assign('value',
					array(
						'data' => $content,
						'success' => TRUE,
					)
				);
			break;
			case 'html' :
				$typoScriptService = $this->contentContext->getTypoScriptService();
				$typoScriptObjectTree = $typoScriptService->getMergedTypoScriptObjectTree($this->contentContext->getCurrentNodePath());
				if ($typoScriptObjectTree === NULL || count($typoScriptObjectTree) === 0) {
					throw new \F3\TYPO3\Controller\Exception\NoTypoScriptConfigurationException('No TypoScript template was found for the current position in the content tree.', 1255513200);
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

				$pageTypoScriptObject->setRenderingContext($renderingContext);
				return $pageTypoScriptObject->render();
		}
	}

	/**
	 * Update content
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\ContentInterface $content The cloned, updated content
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function updateAction(\F3\TYPO3\Domain\Model\Content\ContentInterface $content) {
		$this->contentRepository->update($content);

		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->assign('value',
					array(
						'success' => TRUE
					)
				);
				break;
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
		$this->view->assignErrors($this->argumentsMappingResults->getErrors());
	}

	/**
	 * This controller does not need Fluid templates therefore we use the explicitly
	 * defined view object names to resolve the view.
	 *
	 * @return \F3\FLOW3\MVC\View\ViewInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveView() {
		$view = $this->objectManager->create($this->defaultViewObjectName);
		$view->setControllerContext($this->controllerContext);
		return $view;
	}

}
?>