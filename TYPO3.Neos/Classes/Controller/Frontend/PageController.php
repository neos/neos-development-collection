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
	 * @var string
	 */
	protected $defaultViewObjectName = 'F3\Fluid\View\TemplateView';

	/**
	 * @var \F3\TYPO3\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * @var \F3\TYPO3\Domain\Repository\Content\PageRepository
	 */
	protected $pageRepository;

	/**
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext 
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectContentContext(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$this->contentContext = $contentContext;
	}

	/**
	 * @param \F3\TYPO3\Domain\Repository\Content\PageRepository $pageRepository
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPageRepository(\F3\TYPO3\Domain\Repository\Content\PageRepository $pageRepository) {
		$this->pageRepository = $pageRepository;
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
	 * Shows the page specified in the "page" argument.
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Page $page The page to show
	 * @return string View output for the specified page
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function showAction(\F3\TYPO3\Domain\Model\Content\Page $page) {
		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->assign('value',
					array(
						'data' => $page,
						'success' => TRUE,
					)
				);
			break;
			case 'html' :
				$type = 'default';
				$typoScriptService = $this->contentContext->getTypoScriptService();
				$typoScriptObjectTree = $typoScriptService->getMergedTypoScriptObjectTree($this->contentContext->getCurrentNodePath());
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

				$pageTypoScriptObject->setRenderingContext($renderingContext);
				return $pageTypoScriptObject->render();
		}
	}

	/**
	 * Shows the page specified in the "page" argument.
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Page $page The page to show
	 * @param string $type The type for identifying the TypoScript page object
	 * @return string View output for the specified page
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function show2Action(\F3\TYPO3\Domain\Model\Content\Page $page) {
		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->assign('value',
					array(
						'data' => $page,
						'success' => TRUE,
					)
				);
			break;
		}
	}

	/**
	 * Update a page
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Page $page
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @extdirect
	 */
	public function updateAction(\F3\TYPO3\Domain\Model\Content\Page $page) {
		$this->pageRepository->update($page);

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
				$this->redirect('show', NULL, NULL, array('page' => $page));
		}
	}

	/**
	 * Get information for editing a page
	 *
	 * @todo use some kind of TCA like configuration of page properties and form fields and convert that to Ext JS
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Page $page
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @extdirect
	 */
	public function editAction(\F3\TYPO3\Domain\Model\Content\Page $page) {
		switch ($this->request->getFormat()) {
			case 'extdirect' :
				$this->view->assign('value', array(
					'title' => array(
						'xtype' => 'textfield',
						'allowBlank' => FALSE,
						'fieldLabel' => 'Title'
					),
					'hidden' => array(
						'xtype' => 'checkbox',
						'fieldLabel' => 'Visibility',
						'boxLabel' => 'hidden'
					)
				));
				break;
			default:
				$this->view->assign('page', $page);
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