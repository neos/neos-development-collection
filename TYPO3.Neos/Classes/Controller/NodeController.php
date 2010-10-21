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
 * Controller for rendering the TYPO3 frontend output
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class NodeController extends \F3\FLOW3\MVC\Controller\RestController {

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Service\TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @var string
	 */
	protected $resourceArgumentName = 'node';

	/**
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array(
		 'html' => 'F3\Fluid\View\TemplateView',
		 'extdirect' => 'F3\ExtJS\ExtDirect\View',
		 'json' => 'F3\FLOW3\MVC\View\JsonView',
	);

	/**
	 * Select special error action
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeAction() {
		if ($this->request->getFormat() == 'extdirect') {
			$this->errorMethodName = 'extErrorAction';
		}
	}

	/**
	 * Shows the specified node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function showAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$contentContext = $node->getContext();

		$type = 'default';
		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->setConfiguration(
					array(
						'value' => array(
							'data' => array(
								'only' => array('name', 'path', 'identifier', 'properties', 'contentType'),
								'descend' => array('properties' => array())
							)
						)
					)
				);
				$this->view->assign('value',
					array(
						'data' => $node,
						'success' => TRUE,
					)
				);
			break;
			case 'html' :
				$type = 'default';
				$typoScriptObjectTree = $this->typoScriptService->getMergedTypoScriptObjectTree($contentContext->getCurrentSiteNode(), $node);
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
				$renderingContext->setContentContext($contentContext);

				$pageTypoScriptObject->setRenderingContext($renderingContext);
				return $pageTypoScriptObject->render();
		}
	}

	/**
	 * Creates a new node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $parentNode
	 * @param array $nodeData
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function createAction(\F3\TYPO3CR\Domain\Model\Node $parentNode, array $nodeData) {
		$newNode = $parentNode->createNode($nodeData['nodeName']);

		$newNode->setContentType($nodeData['contentType']);

		foreach ($nodeData['properties'] as $propertyName => $propertyValue) {
			$newNode->setProperty($propertyName, $propertyValue);
		}

		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->setConfiguration(
					array(
						'value' => array(
							'data' => array(
								'only' => array('name', 'path', 'identifier', 'properties', 'contentType'),
								'descend' => array('properties' => array())
							)
						)
					)
				);
				$this->view->assign('value',
					array(
						'data' => $newNode,
						'success' => TRUE,
					)
				);
			break;
		}
	}

	/**
	 * Updates the specified node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 * @todo the updateAction now implicitly saves the node, as the NodeObjectConverter does not clone the node right now. This is a hack, and needs to be cleaned up.
	 */
	public function updateAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->assign('value', array('data' => '', 'success' => TRUE));
			break;
		}
	}

	/**
	 * Deletes the specified node and all of its sub nodes
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return string A response string
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function deleteAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$node->remove();

		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->assign('value', array('data' => '', 'success' => TRUE));
			break;
		}
	}

	/**
	 * Return child nodes of specified node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @param string $contentType
	 * @return string A response string
	 * @author Christian Müller <christian@kitsunet.de>
	 * @extdirect
	 */
	public function getChildNodesAction(\F3\TYPO3CR\Domain\Model\Node $node, $contentType) {

		$childNodes = $node->getChildNodes($contentType);

		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$tempConfiguration =	array(
					'value' => array(
						'descend' => array(
							'data' => array(
								'descend' => array(
								)
							)
						)
					)
				);
				$tempData = array();
				foreach ($childNodes as $key => $childNode) {
					$tempConfiguration['value']['descend']['data']['descend'][$key] = array(
						'only' => array('path'),
					);

					$tempData[$key] = array(
						'id' => $childNode->getPath(),
						'text' => $childNode->getProperty('title')

					);

				}
				$this->view->setConfiguration($tempConfiguration);
				$this->view->assign('value',
					array(
						'data' => $tempData,
						'success' => TRUE,
					)
				);
			break;
		}

	}


}
?>