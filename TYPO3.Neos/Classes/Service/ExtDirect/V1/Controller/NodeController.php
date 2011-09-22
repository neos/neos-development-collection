<?php
namespace TYPO3\TYPO3\Service\ExtDirect\V1\Controller;

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
 * @scope singleton
 */
class NodeController extends \TYPO3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TYPO3\TYPO3\Service\ExtDirect\V1\View\NodeView';

	/**
	 * @inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

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
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function showAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->view->assignNode($node);
	}

	/**
	 * Returns the primary child node (if any) of the specified node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function getPrimaryChildNodeAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->view->assignNode($node->getPrimaryChildNode());
	}

	/**
	 * Return child nodes of specified node for usage in a TreeLoader
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node The node to find child nodes for
	 * @param string $contentTypeFilter A content type filter
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function getChildNodesForTreeAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $contentTypeFilter) {
		$this->view->assignChildNodes($node, $contentTypeFilter, \TYPO3\TYPO3\Service\ExtDirect\V1\View\NodeView::TREESTYLE, 0);
	}

	/**
	 * Return child nodes of specified node with all details and
	 * metadata.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $contentTypeFilter
	 * @param integer $depth levels of childNodes (0 = unlimited)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function getChildNodesAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $contentTypeFilter, $depth) {
		$this->view->assignChildNodes($node, $contentTypeFilter, \TYPO3\TYPO3\Service\ExtDirect\V1\View\NodeView::LISTSTYLE, $depth);
	}

	/**
	 * Return child nodes of specified node with all details and
	 * metadata.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $contentTypeFilter
	 * @param integer $depth levels of childNodes (0 = unlimited)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function getChildNodesFromParentAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $contentTypeFilter, $depth) {
		$this->getChildNodesAction($node->getParent(), $contentTypeFilter, $depth);
	}

	/**
	 * Creates a new node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @param array $nodeData
	 * @param integer $position where the node should be added, -1 is before, 0 is in, 1 is after
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Rens Admiraal <rens@rensnel.nl>
	 * @todo: maybe the actual creation should be put in a helper / service class
	 * @extdirect
	 */
	public function createAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode, array $nodeData, $position) {
		if (!in_array($position, array(-1, 0, 1), TRUE)) {
			throw new \InvalidArgumentException('The position should be one of the following: -1, 0, 1.', 1296132542);
		}

		if (empty($nodeData['nodeName'])) {
			$nodeData['nodeName'] = uniqid('node');
		}

		if ($position === 0) {
			$newNode = $referenceNode->createNode($nodeData['nodeName'], $nodeData['contentType']);
		} else {
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

		if ($nodeData['contentType'] === 'TYPO3.TYPO3:Page') {
			// TODO: Remove that and fix it!
			$this->createEmptySectionNode($newNode);
		}

		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $newNode), 'Frontend\Node', 'TYPO3.TYPO3', '');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Move $node before, into or after $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode
	 * @param integer $position where the node should be added, -1 is before, 0 is in, 1 is after
	 * @return void
	 * @author Aske Ertmann <aske@mocsystems.com>
	 * @extdirect
	 * @fixme Find a better solution that passing -1, 0 and 1
	 */
	public function moveAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode, $position) {
		if (!in_array($position, array(-1, 0, 1), TRUE)) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The position should be one of the following: -1, 0, 1.', 1296132542);
		}

		switch ($position) {
			case -1:
				$node->moveBefore($targetNode);
				break;
			case 0:
				//@TODO: Create a moveInto action on the node domain
				//$node->moveInto($targetNode);
				break;
			case 1:
				$node->moveAfter($targetNode);
				break;
		}

		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.TYPO3', '');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Move $node before $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @extdirect
	 */
	public function moveBeforeAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode) {
		$node->moveBefore($targetNode);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Move $node after $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @extdirect
	 */
	public function moveAfterAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode) {
		$node->moveAfter($targetNode);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Create a section node for the page, so that the user can then create
	 * content elements inside there.
	 *
	 * The section name is currently hardcoded, but should be determined by the currently selected Fluid template
	 * in the future.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $pageNode The page node
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Move section + text node creation to better place (content type triggered)
	 */
	protected function createEmptySectionNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $pageNode) {
		$sectionNode = $pageNode->createNode('main', 'TYPO3.TYPO3:Section');
	}

	/**
	 * Updates the specified node
	 *
	 * Note: We do not call $nodeRepository->update() here, as TYPO3CR has a stateful API for now.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function updateAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->view->assign('value', array('data' => '', 'success' => TRUE));
	}

	/**
	 * Deletes the specified node and all of its sub nodes
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return string A response string
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function deleteAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$node->remove();
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node->getParent()), 'Frontend\Node', 'TYPO3.TYPO3', '');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
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
		$this->view->assignErrors($this->arguments->getValidationResults());
	}
}
?>