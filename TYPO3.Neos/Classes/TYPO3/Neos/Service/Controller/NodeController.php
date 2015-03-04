<?php
namespace TYPO3\Neos\Service\Controller;

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
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Neos\Domain\Service\NodeSearchService;
use TYPO3\Neos\Service\NodeNameGenerator;
use TYPO3\Neos\Service\View\NodeView;
use TYPO3\Neos\View\TypoScriptView;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Exception\NodeException;
use TYPO3\TYPO3CR\TypeConverter\NodeConverter;

/**
 * Service Controller for managing Nodes
 *
 * Note: This controller should be, step-by-step, transformed into a clean REST controller (see NEOS-190 and NEOS-199).
 *       Since this is a rather big endeavor, we slice the elephant and move methods in a clean way from here to the
 *       new NodesController (\TYPO3\Neos\Controller\Service\NodesController)
 */
class NodeController extends AbstractServiceController {

	/**
	 * @var NodeView
	 */
	protected $view;

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'TYPO3\Neos\Service\View\NodeView';

	/**
	 * @Flow\Inject
	 * @var NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var NodeSearchService
	 */
	protected $nodeSearchService;

	/**
	 * @Flow\Inject
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var NodeNameGenerator
	 */
	protected $nodeNameGenerator;

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * Select special error action
	 *
	 * @return void
	 */
	protected function initializeAction() {
		if ($this->arguments->hasArgument('referenceNode')) {
			$this->arguments->getArgument('referenceNode')->getPropertyMappingConfiguration()->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', NodeConverter::REMOVED_CONTENT_SHOWN, TRUE);
		}
		$this->uriBuilder->setRequest($this->request->getMainRequest());
	}

	#
	# Actions which are not yet refactored to REST below (see NEOS-199):
	#

	/**
	 * Return child nodes of specified node for usage in a TreeLoader
	 *
	 * @param Node $node The node to find child nodes for
	 * @param string $nodeTypeFilter A node type filter
	 * @param integer $depth levels of childNodes (0 = unlimited)
	 * @param Node $untilNode expand the child nodes until $untilNode is reached, independent of $depth
	 * @return void
	 */
	public function getChildNodesForTreeAction(Node $node, $nodeTypeFilter, $depth, Node $untilNode) {
		$this->view->assignChildNodes($node, $nodeTypeFilter, NodeView::STYLE_TREE, $depth, $untilNode);
	}

	/**
	 * Return child nodes of specified node for usage in a TreeLoader based on filter
	 *
	 * @param Node $node The node to find child nodes for
	 * @param string $term
	 * @param string $nodeType
	 * @return void
	 */
	public function filterChildNodesForTreeAction(Node $node, $term, $nodeType) {
		$nodeTypes = strlen($nodeType) > 0 ? array($nodeType) : array_keys($this->nodeTypeManager->getSubNodeTypes('TYPO3.Neos:Document', FALSE));
		$this->view->assignFilteredChildNodes(
			$node,
			$this->nodeSearchService->findByProperties($term, $nodeTypes, $node->getContext())
		);
	}

	/**
	 * Creates a new node
	 *
	 * @param Node $referenceNode
	 * @param array $nodeData
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return void
	 * @throws \InvalidArgumentException
	 * @todo maybe the actual creation should be put in a helper / service class
	 */
	public function createAction(Node $referenceNode, array $nodeData, $position) {
		$newNode = $this->createNewNode($referenceNode, $nodeData, $position);
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $newNode), 'Frontend\Node', 'TYPO3.Neos');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Creates a new node and renders the node inside the containing section
	 *
	 * @param Node $referenceNode
	 * @param string $typoScriptPath The TypoScript path of the collection
	 * @param array $nodeData
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function createAndRenderAction(Node $referenceNode, $typoScriptPath, array $nodeData, $position) {
		$newNode = $this->createNewNode($referenceNode, $nodeData, $position);

		$view = new TypoScriptView();
		$this->controllerContext->getRequest()->setFormat('html');
		$view->setControllerContext($this->controllerContext);
		$view->setOption('enableContentCache', FALSE);

		$view->setTypoScriptPath($typoScriptPath);
		$view->assign('value', $newNode->getParent());

		$result = $view->render();
		$this->response->setContent(json_encode((object)array('collectionContent' => $result, 'nodePath' => $newNode->getContextPath())));

		return '';
	}

	/**
	 * Creates a new node and returns tree structure
	 *
	 * @param Node $referenceNode
	 * @param array $nodeData
	 * @param string $position where the node should be added, -1 is before, 0 is in, 1 is after
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function createNodeForTheTreeAction(Node $referenceNode, array $nodeData, $position) {
		$newNode = $this->createNewNode($referenceNode, $nodeData, $position);
		$this->view->assign('value', array('data' => $this->view->collectTreeNodeData($newNode), 'success' => TRUE));
	}

	/**
	 * @param Node $referenceNode
	 * @param array $nodeData
	 * @param string $position
	 * @return Node
	 * @throws \InvalidArgumentException
	 */
	protected function createNewNode(Node $referenceNode, array $nodeData, $position) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new \InvalidArgumentException('The position should be one of the following: "before", "into", "after".', 1347133640);
		}
		$nodeType = $this->nodeTypeManager->getNodeType($nodeData['nodeType']);

		if ($nodeType->isOfType('TYPO3.Neos:Document') && !isset($nodeData['properties']['uriPathSegment']) && isset($nodeData['properties']['title'])) {
			$nodeData['properties']['uriPathSegment'] = $nodeData['properties']['title'];
		}

		$proposedNodeName = isset($nodeData['nodeName']) ? $nodeData['nodeName'] : NULL;
		$nodeData['nodeName'] = $this->nodeNameGenerator->generateUniqueNodeName($this->getDesignatedParentNode($referenceNode, $position), $proposedNodeName);

		if ($position === 'into') {
			$newNode = $referenceNode->createNode($nodeData['nodeName'], $nodeType);
		} else {
			$parentNode = $referenceNode->getParent();
			$newNode = $parentNode->createNode($nodeData['nodeName'], $nodeType);

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

		$this->nodeDataRepository->persistEntities();
		return $newNode;
	}

	/**
	 * Move $node before, into or after $targetNode
	 *
	 * @param Node $node
	 * @param Node $targetNode
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return void
	 * @throws NodeException
	 */
	public function moveAction(Node $node, Node $targetNode, $position) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new NodeException('The position should be one of the following: "before", "into", "after".', 1296132542);
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
		$this->nodeDataRepository->persistEntities();

		$data = array('newNodePath' => $node->getContextPath());
		if ($node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			$data['nextUri'] = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos');
		}
		$this->view->assign('value', array('data' => $data, 'success' => TRUE));
	}

	/**
	 * Copy $node before, into or after $targetNode
	 *
	 * @param Node $node the node to be copied
	 * @param Node $targetNode the target node to be copied "to", see $position
	 * @param string $position where the node should be added in relation to $targetNode (allowed: before, into, after)
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @throws NodeException
	 */
	public function copyAction(Node $node, Node $targetNode, $position, $nodeName = NULL) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new NodeException('The position should be one of the following: "before", "into", "after".', 1346832303);
		}

		if (!empty($nodeName)) {
			$nodeName = $this->nodeNameGenerator->generateUniqueNodeName($this->getDesignatedParentNode($targetNode, $position), $nodeName);
		} else {
			$nodeName = $this->nodeNameGenerator->generateUniqueNodeName($this->getDesignatedParentNode($targetNode, $position));
		}

		switch ($position) {
			case 'before':
				$copiedNode = $node->copyBefore($targetNode, $nodeName);
			break;
			case 'after':
				$copiedNode = $node->copyAfter($targetNode, $nodeName);
			break;
			case 'into':
			default:
				$copiedNode = $node->copyInto($targetNode, $nodeName);
		}
		$this->nodeDataRepository->persistEntities();

		$q = new FlowQuery(array($copiedNode));
		$closestDocumentNode = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);

		$requestData = array(
			'nextUri' => $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $closestDocumentNode), 'Frontend\Node', 'TYPO3.Neos'),
			'newNodePath' => $copiedNode->getContextPath()
		);

		if ($node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			$requestData['nodeUri'] = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $copiedNode), 'Frontend\Node', 'TYPO3.Neos');
		}

		$this->view->assign('value', array('data' => $requestData, 'success' => TRUE));
	}

	/**
	 * Updates the specified node. Returns the following data:
	 * - the (possibly changed) workspace name of the node
	 * - the URI of the closest document node. If $node is a document node (f.e. a Page), the own URI is returned.
	 *   This is important to handle renamings of nodes correctly.
	 *
	 * Note: We do not call $nodeDataRepository->update() here, as TYPO3CR has a stateful API for now.
	 *
	 * @param Node $node
	 * @return void
	 */
	public function updateAction(Node $node) {
		$this->nodeDataRepository->persistEntities();
		$q = new FlowQuery(array($node));
		$closestDocumentNode = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $closestDocumentNode), 'Frontend\Node', 'TYPO3.Neos');
		$this->view->assign('value', array('data' => array('workspaceNameOfNode' => $node->getWorkspace()->getName(), 'nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Deletes the specified node and all of its sub nodes
	 *
	 * @param Node $node
	 * @return void
	 */
	public function deleteAction(Node $node) {
		$this->nodeDataRepository->persistEntities();
		$q = new FlowQuery(array($node));
		$node->remove();
		$closestDocumentNode = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $closestDocumentNode), 'Frontend\Node', 'TYPO3.Neos');

		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Search a page, needed for internal links.
	 *
	 * @param string $query
	 * @return void
	 */
	public function searchPageAction($query) {
		$searchResult = array();

		$documentNodeTypes = $this->nodeTypeManager->getSubNodeTypes('TYPO3.Neos:Document');
		/** @var NodeInterface $node */
		foreach ($this->nodeSearchService->findByProperties($query, $documentNodeTypes, $this->createContext('live')) as $node) {
			$searchResult[$node->getPath()] = $this->processNodeForEditorPlugins($node);
		}

		$this->view->assign('value', array('searchResult' => $searchResult, 'success' => TRUE));
	}

	/**
	 * Get the page by the node path, needed for internal links.
	 *
	 * @param string $nodePath
	 * @return void
	 */
	public function getPageByNodePathAction($nodePath) {
		$contentContext = $this->createContext('live');

		$node = $contentContext->getNode($nodePath);
		$this->view->assign('value', array('node' => $this->processNodeForEditorPlugins($node), 'success' => TRUE));
	}

	/**
	 * Returns an array with the data needed by for example the Hallo and Aloha
	 * link plugins to represent the passed Node instance.
	 *
	 * @param NodeInterface $node
	 * @return array
	 */
	protected function processNodeForEditorPlugins(NodeInterface $node) {
		return array(
			'id' => $node->getPath(),
			'name' => $node->getLabel(),
			'url' => $this->uriBuilder->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos'),
			'type' => 'neos/internal-link'
		);
	}

	/**
	 * Create a Context for a workspace given by name to be used in this controller.
	 *
	 * @param string $workspaceName Name of the current workspace
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected function createContext($workspaceName) {
		$contextProperties = array(
			'workspaceName' => $workspaceName
		);

		return $this->contextFactory->create($contextProperties);
	}

	/**
	 * @param NodeInterface $targetNode
	 * @param string $position
	 * @return NodeInterface
	 */
	protected function getDesignatedParentNode(NodeInterface $targetNode, $position) {
		$referenceNode = $targetNode;
		if (in_array($position, array('before', 'after'))) {
			$referenceNode = $targetNode->getParent();
		}

		return $referenceNode;
	}
}
