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
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

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
	 * @param integer $depth levels of childNodes (0 = unlimited)
	 * @return void
	 * @ExtDirect
	 */
	public function getChildNodesForTreeAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $contentTypeFilter, $depth) {
		$this->view->assignChildNodes($node, $contentTypeFilter, \TYPO3\TYPO3\Service\ExtDirect\V1\View\NodeView::TREESTYLE, $depth);
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
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return void
	 * @throws \InvalidArgumentException
	 * @todo maybe the actual creation should be put in a helper / service class
	 * @ExtDirect
	 */
	public function createAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode, array $nodeData, $position) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new \InvalidArgumentException('The position should be one of the following: "before", "into", "after".', 1347133640);
		}

		if (empty($nodeData['nodeName'])) {
			$nodeData['nodeName'] = uniqid('node');
		}
		$contentType = $this->contentTypeManager->getContentType($nodeData['contentType']);

		if ($position === 'into') {
			$newNode = $referenceNode->createNode($nodeData['nodeName'], $contentType);
		} else {
			$parentNode = $referenceNode->getParent();
			$newNode = $parentNode->createNode($nodeData['nodeName'], $contentType);

			if ($position === 'before') {
				$newNode->moveBefore($referenceNode);
			} else {
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
	 * Creates a new node and renders the node inside the containing section
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @param string $typoScriptPath The TypoScript path of the collection
	 * @param array $nodeData
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return void
	 * @throws \InvalidArgumentException
	 * @ExtDirect
	 */
	public function createAndRenderAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode, $typoScriptPath, array $nodeData, $position) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new \InvalidArgumentException('The position should be one of the following: "before", "into", "after".', 1296132542);
		}

		if (empty($nodeData['nodeName'])) {
			$nodeData['nodeName'] = uniqid('node');
		}
		$contentType = $this->contentTypeManager->getContentType($nodeData['contentType']);

		if ($position === 'into') {
			$newNode = $referenceNode->createNode($nodeData['nodeName'], $contentType);
		} else {
			$parentNode = $referenceNode->getParent();
			$newNode = $parentNode->createNode($nodeData['nodeName'], $contentType);

			if ($position === 'before') {
				$newNode->moveBefore($referenceNode);
			} elseif ($position === 'after') {
				$newNode->moveAfter($referenceNode);
			}

				// We are creating a node *inside* another node; so the client side
				// currently expects the whole parent TypoScript path to be rendered.
				// Thus, we split off the last segment of the TypoScript path.
			$typoScriptPath = substr($typoScriptPath, 0, strrpos($typoScriptPath, '/'));
		}

		if (isset($nodeData['properties']) && is_array($nodeData['properties'])) {
			foreach ($nodeData['properties'] as $propertyName => $propertyValue) {
				$newNode->setProperty($propertyName, $propertyValue);
			}
		}

		$this->view = new \TYPO3\TYPO3\View\TypoScriptView();
		$this->view->setControllerContext($this->controllerContext);

		$this->view->setTypoScriptPath($typoScriptPath);
		$this->view->assign('value', $newNode->getParent());

		$result = $this->view->render();
		$this->response->setResult(array('collectionContent' => $result, 'nodePath' => $newNode->getContextPath()));
		$this->response->setSuccess(TRUE);

		return '';
	}

	/**
	 * Move $node before, into or after $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException
	 * @ExtDirect
	 */
	public function moveAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode, $position) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The position should be one of the following: "before", "into", "after".', 1296132542);
		}

		switch ($position) {
			case 'before':
				$node->moveBefore($targetNode);
			break;
			case 'into':
				$node->moveInto($targetNode);
			break;
			case 'after':
				$node->moveAfter($targetNode);
		}

		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.TYPO3', '');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri, 'newNodePath' => $node->getContextPath()), 'success' => TRUE));
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
	 * Move $node into $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode
	 * @return void
	 * @ExtDirect
	 */
	public function moveIntoAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode) {
		$node->moveInto($targetNode);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Copy $node before, into or after $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException
	 * @ExtDirect
	 */
	public function copyAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode, $position, $nodeName = '') {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The position should be one of the following: "before", "into", "after".', 1346832303);
		}

		$nodeName = $nodeName === '' ? uniqid('node') : $nodeName;

		switch ($position) {
			case 'before':
				$node->copyBefore($targetNode, $nodeName);
			break;
			case 'into':
				$node->copyInto($targetNode, $nodeName);
			break;
			case 'after':
				$node->copyAfter($targetNode, $nodeName);
		}

		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.TYPO3', '');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Copy $node before $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @ExtDirect
	 */
	public function copyBeforeAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode, $nodeName = '') {
		$nodeName = $nodeName === '' ? uniqid('node') : $nodeName;
		$node->copyBefore($targetNode, $nodeName);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Copy $node after $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @ExtDirect
	 */
	public function copyAfterAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode, $nodeName = '') {
		$nodeName = $nodeName === '' ? uniqid('node') : $nodeName;
		$node->copyAfter($targetNode, $nodeName);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Copy $node into $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @ExtDirect
	 */
	public function copyIntoAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $targetNode, $nodeName = '') {
		$nodeName = $nodeName === '' ? uniqid('node') : $nodeName;
		$node->copyInto($targetNode, $nodeName);
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
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.TYPO3', '');
		$this->view->assign('value', array('data' => array('workspaceNameOfNode' => $node->getWorkspace()->getName(), 'nextUri' => $nextUri), 'success' => TRUE));
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