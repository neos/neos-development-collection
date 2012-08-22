<?php
namespace TYPO3\TYPO3\Service\ExtDirect\V1\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\ExtJS\Annotations\ExtDirect;

/**
 * ExtDirect Controller for managing Nodes
 *
 * @FLOW3\Scope("singleton")
 */
class NodeController extends \TYPO3\FLOW3\Mvc\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TYPO3\TYPO3\Service\ExtDirect\V1\View\NodeView';

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3\Domain\Service\NodeSearchService
	 */
	protected $nodeSearchService;

	/**
	 * Select special error action
	 *
	 * @return void
	 */
	protected function initializeAction() {
		$this->errorMethodName = 'extErrorAction';
	}

	/**
	 * Returns the specified node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 * @ExtDirect
	 */
	public function showAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->view->assignNode($node);
	}

	/**
	 * Returns the primary child node (if any) of the specified node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 * @ExtDirect
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
	 * @ExtDirect
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
	 * @ExtDirect
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
	 * @ExtDirect
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
	 * @throws \InvalidArgumentException
	 * @todo maybe the actual creation should be put in a helper / service class
	 * @ExtDirect
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
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException
	 * @ExtDirect
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
				// TODO: Create a moveInto action on the node domain
				// $node->moveInto($targetNode);
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
	 * @ExtDirect
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
	 * @ExtDirect
	 */
	public function moveAfterAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode) {
		$node->moveAfter($targetNode);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Updates the specified node
	 *
	 * Note: We do not call $nodeRepository->update() here, as TYPO3CR has a stateful API for now.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 * @ExtDirect
	 */
	public function updateAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->view->assign('value', array('data' => array('workspaceNameOfNode' => $node->getWorkspace()->getName()), 'success' => TRUE));
	}

	/**
	 * Deletes the specified node and all of its sub nodes
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 * @ExtDirect
	 */
	public function deleteAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$node->remove();
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node->getParent()), 'Frontend\Node', 'TYPO3.TYPO3', '');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Search a page, needed for internal links.
	 *
	 * @param string $query
	 * @return void
	 * @ExtDirect
	 */
	public function searchPageAction($query) {
		$nodes = $this->nodeSearchService->findByProperties($query, array('TYPO3.TYPO3:Page'));

		$searchResult = array();

		$contentContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('live');
		foreach ($nodes as $uninitializedNode) {
			$node = $contentContext->getNode($uninitializedNode->getPath());
			$searchResult[] = $this->processNodeForAlohaRepository($node);
		}

		$this->view->assign('value', array('searchResult' => $searchResult, 'success' => TRUE));
	}

	/**
	 * Get the page by the node path, needed for internal links.
	 *
	 * @param string $nodePath
	 * @return void
	 * @ExtDirect
	 */
	public function getPageByNodePathAction($nodePath) {
		$contentContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('live');
		$node = $contentContext->getNode($nodePath);
		$this->view->assign('value', array('node' => $this->processNodeForAlohaRepository($node), 'success' => TRUE));
	}

	/**
	 * Returns an array with the data needed by the Aloha link browser to represent the passed
	 * NodeInterface instance.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return array
	 */
	protected function processNodeForAlohaRepository(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		return array(
			'id' => $node->getPath(),
			'name' => $node->getLabel(),
			'url' => $this->uriBuilder->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.TYPO3', ''),
			'type' => 'phoenix/internal-link'
		);
	}

	/**
	 * A preliminary error action for handling validation errors
	 * by assigning them to the ExtDirect View that takes care of
	 * converting them.
	 *
	 * @return void
	 */
	public function extErrorAction() {
		$this->view->assignErrors($this->arguments->getValidationResults());
	}
}
?>