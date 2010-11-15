<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Service\Rest\V1\Controller;

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
 * REST Controller for managing Nodes
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class NodeController extends \F3\FLOW3\MVC\Controller\RestController {

	/**
	 * @var string
	 */
	protected $resourceArgumentName = 'node';

	/**
	 * @var array
	 */
	protected $supportedFormats = array('json', 'html');

	/**
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array(
		 'html' => 'F3\TYPO3\View\TypoScriptView',
		 'json' => 'F3\FLOW3\MVC\View\JsonView',
	);

	/**
	 *
	 */
	public function indexAction() {

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
		switch ($this->request->getFormat()) {
			case 'json' :
				$this->view->setConfiguration(
					array(
						'value' => array(
							'_only' => array('name', 'path', 'identifier', 'properties', 'contentType'),
							'_descend' => array('properties' => array())
						)
					)
				);
			break;
		}
		$this->view->assign('value', $node);
	}

	/**
	 * Creates a new node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $parentNode
	 * @param array $nodeData
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createAction(\F3\TYPO3CR\Domain\Model\Node $parentNode, array $nodeData) {
		$newNode = $parentNode->createNode($nodeData['nodeName']);
		$newNode->setContentType($nodeData['contentType']);

		foreach ($nodeData['properties'] as $propertyName => $propertyValue) {
			$newNode->setProperty($propertyName, $propertyValue);
		}
		$this->redirect('show', NULL, NULL, array('node' => $newNode), 0, 201);
	}

	/**
	 * Updates the specified node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo the updateAction now implicitly saves the node, as the NodeObjectConverter does not clone the node right now. This is a hack, and needs to be cleaned up.
	 */
	public function updateAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$this->redirect('show', NULL, NULL, array('node' => $node), 0, 200);
	}

	/**
	 * Deletes the specified node and all of its sub nodes
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function deleteAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$node->remove();
		$this->redirect('show', NULL, NULL, array('node' => $node->getParent()));
	}

}
?>