<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Service\ExtDirect\V1\Controller;

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
 * ExtDirect Controller for managing Nodes
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope singleton
 */
class NodeController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'F3\TYPO3\Service\ExtDirect\V1\View\NodeView';

	/**
	 * Select special error action
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeAction() {
		$this->errorMethodName = 'extErrorAction';
	}

	/**
	 * Returns the specified node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function showAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$this->view->assignNode($node);
	}

	/**
	 * Returns the primary child node (if any) of the specified node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function getPrimaryChildNodeAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$this->view->assignNode($node->getPrimaryChildNode());
	}

	/**
	 * Return child nodes of specified node for usage in a TreeLoader
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node The node to find child nodes for
	 * @param string $contentTypeFilter A content type filter
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function getChildNodesForTreeAction(\F3\TYPO3CR\Domain\Model\Node $node, $contentTypeFilter) {
		$this->view->assignChildNodes($node, $contentTypeFilter, \F3\TYPO3\Service\ExtDirect\V1\View\NodeView::TREESTYLE, 0);
	}

	/**
	 * Return child nodes of specified node with all details and
	 * metadata.
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @param string $contentTypeFilter
	 * @param integer $depth levels of childNodes (0 = unlimited)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function getChildNodesAction(\F3\TYPO3CR\Domain\Model\Node $node, $contentTypeFilter, $depth) {
		$this->view->assignChildNodes($node, $contentTypeFilter, \F3\TYPO3\Service\ExtDirect\V1\View\NodeView::LISTSTYLE, $depth);
	}

	/**
	 * Creates a new node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $referenceNode
	 * @param array $nodeData
	 * @param integer $position where the node should be added, -1 is before, 0 is in, 1 is after
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Rens Admiraal <rens@rensnel.nl>
	 * @todo: maybe the actual creation should be put in a helper / service class
	 * @extdirect
	 */
	public function createAction(\F3\TYPO3CR\Domain\Model\Node $referenceNode, array $nodeData, $position) {
		if (!in_array($position, array(-1, 0, 1), TRUE)) {
			throw new \F3\TYPO3CR\Exception\NodeException('The position should be one of the following: -1, 0, 1.', 1296132542);
		}

			// Generate a nodeName if not given
		if (empty($nodeData['nodeName'])) {
			$nodeData['nodeName'] = uniqid();
		}

		if ($position === 0) {
				// Place the new node in the referenceNode
			$newNode = $referenceNode->createNode($nodeData['nodeName'], $nodeData['contentType']);
		} else {
				// Place the node before or after the reference
			$parentNode = $referenceNode->getParent();
			$newNode = $parentNode->createNode($nodeData['nodeName'], $nodeData['contentType']);

			if ($position === -1) {
				$newNode->moveBefore($referenceNode);
			} elseif ($position === 1) {
				$newNode->moveAfter($referenceNode);
			}
		}

		if (isset($nodeData['properties']) && is_array($nodeData['properties'])) {
			foreach ($nodeData['properties'] as $propertyName => $propertyValue) {
				$newNode->setProperty($propertyName, $propertyValue);
			}
		}

		if ($nodeData['contentType'] === 'TYPO3:Page') {
			$this->createTypeHereTextNode($newNode);
		}

		$nextUri = $this->uriBuilder
			->reset()
			->setFormat('html')
			->setCreateAbsoluteUri(TRUE)
			->uriFor('show', array('node' => $newNode, 'service' => 'REST'), 'Node', 'TYPO3', 'Service\Rest\V1');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Move $node before $targetNode
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @param F3\TYPO3CR\Domain\Model\Node $targetNode
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @extdirect
	 */
	public function moveBeforeAction(\F3\TYPO3CR\Domain\Model\Node $node, \F3\TYPO3CR\Domain\Model\Node $targetNode) {
		$node->moveBefore($targetNode);
	}

	/**
	 * Move $node after $targetNode
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @param F3\TYPO3CR\Domain\Model\Node $targetNode
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @extdirect
	 */
	public function moveAfterAction(\F3\TYPO3CR\Domain\Model\Node $node, \F3\TYPO3CR\Domain\Model\Node $targetNode) {
		$node->moveAfter($targetNode);
	}

	/**
	 * Create a section + text node for the new page.
	 *
	 * The section name is currently hardcoded, but should be determined by the currently selected Fluid template
	 * in the future. This whole text-element-creation should also be triggered by the Content Type once we have
	 * support for that.
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $pageNode The page node
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Move section + text node creation to better place (content type triggered)
	 */
	protected function createTypeHereTextNode(\F3\TYPO3CR\Domain\Model\Node $pageNode) {
		$sectionNode = $pageNode->createNode('main', 'TYPO3:Section');
		$textNode = $sectionNode->createNode(uniqid(), 'TYPO3:Text');
		$textNode->setProperty('text', '<em>[ Start typing here ]</em>');
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
		$this->view->assign('value', array('data' => '', 'success' => TRUE));
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
		$nextUri = $this->uriBuilder
			->reset()
			->setFormat('html')
			->setCreateAbsoluteUri(TRUE)
			->uriFor('show', array('node' => $node->getParent(), 'service' => 'REST'), 'Node', 'TYPO3', 'Service\Rest\V1');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}
}
?>