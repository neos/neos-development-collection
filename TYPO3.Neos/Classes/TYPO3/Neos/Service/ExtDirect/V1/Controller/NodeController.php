<?php
namespace TYPO3\Neos\Service\ExtDirect\V1\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\ExtJS\Annotations\ExtDirect;

/**
 * ExtDirect Controller for managing Nodes
 *
 * @Flow\Scope("singleton")
 */
class NodeController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TYPO3\Neos\Service\ExtDirect\V1\View\NodeView';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Service\NodeSearchService
	 */
	protected $nodeSearchService;

	/**
	 * Select special error action
	 *
	 * @return void
	 */
	protected function initializeAction() {
		$this->errorMethodName = 'extErrorAction';
		if ($this->arguments->hasArgument('referenceNode')) {
			$this->arguments->getArgument('referenceNode')->getPropertyMappingConfiguration()->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', \TYPO3\TYPO3CR\TypeConverter\NodeConverter::REMOVED_CONTENT_SHOWN, TRUE);
		}
	}

	/**
	 * Returns the specified node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return void
	 * @ExtDirect
	 */
	public function showAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		$this->view->assignNode($node);
	}

	/**
	 * Returns the primary child node (if any) of the specified node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return void
	 * @ExtDirect
	 */
	public function getPrimaryChildNodeAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		$this->view->assignNode($node->getPrimaryChildNode());
	}

	/**
	 * Return child nodes of specified node for usage in a TreeLoader
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node The node to find child nodes for
	 * @param string $contentTypeFilter A content type filter
	 * @param integer $depth levels of childNodes (0 = unlimited)
	 * @return void
	 * @ExtDirect
	 */
	public function getChildNodesForTreeAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, $contentTypeFilter, $depth) {
		$this->view->assignChildNodes($node, $contentTypeFilter, \TYPO3\Neos\Service\ExtDirect\V1\View\NodeView::STYLE_TREE, $depth);
	}

	/**
	 * Return child nodes of specified node with all details and
	 * metadata.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @param string $contentTypeFilter
	 * @param integer $depth levels of childNodes (0 = unlimited)
	 * @return void
	 * @ExtDirect
	 */
	public function getChildNodesAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, $contentTypeFilter, $depth) {
		$this->view->assignChildNodes($node, $contentTypeFilter, \TYPO3\Neos\Service\ExtDirect\V1\View\NodeView::STYLE_LIST, $depth);
	}

	/**
	 * Return child nodes of specified node with all details and
	 * metadata.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @param string $contentTypeFilter
	 * @param integer $depth levels of childNodes (0 = unlimited)
	 * @return void
	 * @ExtDirect
	 */
	public function getChildNodesFromParentAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, $contentTypeFilter, $depth) {
		$this->getChildNodesAction($node->getParent(), $contentTypeFilter, $depth);
	}

	/**
	 * Creates a new node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param array $nodeData
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return void
	 * @throws \InvalidArgumentException
	 * @todo maybe the actual creation should be put in a helper / service class
	 * @ExtDirect
	 */
	public function createAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode, array $nodeData, $position) {
		$newNode = $this->createNewNode($referenceNode, $nodeData, $position);
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $newNode), 'Frontend\Node', 'TYPO3.Neos', '');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Creates a new node and renders the node inside the containing section
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param string $typoScriptPath The TypoScript path of the collection
	 * @param array $nodeData
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return string
	 * @throws \InvalidArgumentException
	 * @ExtDirect
	 */
	public function createAndRenderAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode, $typoScriptPath, array $nodeData, $position) {
		$newNode = $this->createNewNode($referenceNode, $nodeData, $position);
		if ($position !== 'into') {
				// We are creating a node *inside* another section; so the client side
				// currently expects the whole parent TypoScript path to be rendered.
				// Thus, we split off the last segment of the TypoScript path.
			$typoScriptPath = substr($typoScriptPath, 0, strrpos($typoScriptPath, '/'));
		}

		$view = new \TYPO3\Neos\View\TypoScriptView();
		$this->controllerContext->getRequest()->setFormat('html');
		$view->setControllerContext($this->controllerContext);

		$view->setTypoScriptPath($typoScriptPath);
		$view->assign('value', $newNode->getParent());

		$result = $view->render();
		$this->response->setResult(array('collectionContent' => $result, 'nodePath' => $newNode->getContextPath()));
		$this->response->setSuccess(TRUE);

		return '';
	}

	/**
	 * Creates a new node and returns tree structure
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param array $nodeData
	 * @param string $position where the node should be added, -1 is before, 0 is in, 1 is after
	 * @return void
	 * @throws \InvalidArgumentException
	 * @todo maybe the actual creation should be put in a helper / service class
	 * @ExtDirect
	 */
	public function createNodeForTheTreeAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode, array $nodeData, $position) {
		$newNode = $this->createNewNode($referenceNode, $nodeData, $position);
		$this->view->assign('value', array('data' => $this->view->collectTreeNodeData($newNode), 'success' => TRUE));
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param array $nodeData
	 * @param string $position
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node
	 * @throws \InvalidArgumentException
	 */
	protected function createNewNode(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode, array $nodeData, $position) {
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

		return $newNode;
	}

	/**
	 * Move $node before, into or after $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException
	 * @ExtDirect
	 */
	public function moveAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode, $position) {
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

		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos', '');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri, 'newNodePath' => $node->getContextPath()), 'success' => TRUE));
	}

	/**
	 * Move $node before $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode
	 * @return void
	 * @ExtDirect
	 */
	public function moveBeforeAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode) {
		$node->moveBefore($targetNode);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Move $node after $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode
	 * @return void
	 * @ExtDirect
	 */
	public function moveAfterAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode) {
		$node->moveAfter($targetNode);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Move $node into $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode
	 * @return void
	 * @ExtDirect
	 */
	public function moveIntoAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode) {
		$node->moveInto($targetNode);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Copy $node before, into or after $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException
	 * @ExtDirect
	 */
	public function copyAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode, $position, $nodeName = '') {
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

		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos', '');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Copy $node before $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @ExtDirect
	 */
	public function copyBeforeAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode, $nodeName = '') {
		$nodeName = $nodeName === '' ? uniqid('node') : $nodeName;
		$node->copyBefore($targetNode, $nodeName);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Copy $node after $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @ExtDirect
	 */
	public function copyAfterAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode, $nodeName = '') {
		$nodeName = $nodeName === '' ? uniqid('node') : $nodeName;
		$node->copyAfter($targetNode, $nodeName);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Copy $node into $targetNode
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @ExtDirect
	 */
	public function copyIntoAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $targetNode, $nodeName = '') {
		$nodeName = $nodeName === '' ? uniqid('node') : $nodeName;
		$node->copyInto($targetNode, $nodeName);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Updates the specified node. Returns the following data:
	 * - the (possibly changed) workspace name of the node
	 * - the URI of the closest folder node. If $node is a folder node (f.e. a Page), the own URI is returned.
	 *   This is important to handle renamings of nodes correctly.
	 *
	 * Note: We do not call $nodeRepository->update() here, as TYPO3CR has a stateful API for now.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return void
	 * @ExtDirect
	 */
	public function updateAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		$closestFolderNode = $node;
		while (!$closestFolderNode->getContentType()->isOfType('TYPO3.TYPO3CR:Folder')) {
			$closestFolderNode = $closestFolderNode->getParent();
		}
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $closestFolderNode), 'Frontend\Node', 'TYPO3.Neos', '');
		$this->view->assign('value', array('data' => array('workspaceNameOfNode' => $node->getWorkspace()->getName(), 'nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Deletes the specified node and all of its sub nodes
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return void
	 * @ExtDirect
	 */
	public function deleteAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		$node->remove();
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node->getParent()), 'Frontend\Node', 'TYPO3.Neos', '');
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
		$contentContext = new \TYPO3\Neos\Domain\Service\ContentContext('live');
		$this->nodeRepository->setContext($contentContext);

		$nodes = $this->nodeSearchService->findByProperties($query, array('TYPO3.Neos.ContentTypes:Page'));

		$searchResult = array();

		foreach ($nodes as $uninitializedNode) {
			$node = $contentContext->getNode($uninitializedNode->getPath());
			$searchResult[] = $this->processNodeForEditorPlugins($node);
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
		$contentContext = new \TYPO3\Neos\Domain\Service\ContentContext('live');
		$node = $contentContext->getNode($nodePath);
		$this->view->assign('value', array('node' => $this->processNodeForEditorPlugins($node), 'success' => TRUE));
	}

	/**
	 * Returns an array with the data needed by for example the Hallo and Aloha
	 * link plugins to represent the passed PersistentNodeInterface instance.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return array
	 */
	protected function processNodeForEditorPlugins(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		return array(
			'id' => $node->getPath(),
			'name' => $node->getLabel(),
			'url' => $this->uriBuilder->setLinkProtectionEnabled(FALSE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos', ''),
			'type' => 'neos/internal-link'
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