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
use TYPO3\Neos\Service\View\NodeView;
use TYPO3\Neos\View\TypoScriptView;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
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
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array(
		'html' => 'TYPO3\Neos\Service\View\NodeView',
		'json' => 'TYPO3\Flow\Mvc\View\JsonView'
	);

	/**
	 * @var array
	 */
	protected $supportedMediaTypes = array(
		'text/html',
		'application/json'
	);

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
	 * @var NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * @Flow\Inject
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\NodeOperations
	 */
	protected $nodeOperations;

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
		if (in_array($this->request->getControllerActionName(), array('update', 'updateAndRender'), TRUE)) {
			// Set PropertyMappingConfiguration for updating the node (and attached objects)
			$propertyMappingConfiguration = $this->arguments->getArgument('node')->getPropertyMappingConfiguration();
			$propertyMappingConfiguration->allowOverrideTargetType();
			$propertyMappingConfiguration->allowAllProperties();
			$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
			$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
		}
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
		$context = $node->getContext();
		if ($term !== '') {
			$nodes = $this->nodeSearchService->findByProperties($term, $nodeTypes, $context, $node);
		} else {
			$nodes = array();
			$nodeDataRecords = $this->nodeDataRepository->findByParentAndNodeTypeRecursively($node->getPath(), implode(',', $nodeTypes), $context->getWorkspace(), $context->getDimensions());
			foreach ($nodeDataRecords as $nodeData) {
				$matchedNode = $this->nodeFactory->createFromNodeData($nodeData, $context);
				if ($matchedNode !== NULL) {
					$nodes[$matchedNode->getPath()] = $matchedNode;
				}
			}
		}
		$this->view->assignFilteredChildNodes(
			$node,
			$nodes
		);
	}

	/**
	 * Creates a new node
	 *
	 * We need to call persistAll() in order to return the nextUri. We can't persist only the nodes in NodeDataRepository
	 * because they might be connected to images / resources which need to be updated at the same time.
	 *
	 * @param Node $referenceNode
	 * @param array $nodeData
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return void
	 */
	public function createAction(Node $referenceNode, array $nodeData, $position) {
		$newNode = $this->nodeOperations->create($referenceNode, $nodeData, $position);

		if ($this->request->getHttpRequest()->isMethodSafe() === FALSE) {
			$this->persistenceManager->persistAll();
		}

		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $newNode), 'Frontend\Node', 'TYPO3.Neos');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Creates a new node and renders the node inside the containing content collection.
	 *
	 * @param Node $referenceNode
	 * @param string $typoScriptPath The TypoScript path of the collection
	 * @param array $nodeData
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return string
	 */
	public function createAndRenderAction(Node $referenceNode, $typoScriptPath, array $nodeData, $position) {
		$newNode = $this->nodeOperations->create($referenceNode, $nodeData, $position);

		$result = $this->renderNode($newNode->getParent(), $typoScriptPath);

		// TODO: For some reason persistAll BEFORE rendering will break images (probably something in doctrine), so do not change nodes, persist and render. If change and render happens, persist afterwards.
		if ($this->request->getHttpRequest()->isMethodSafe() === FALSE) {
			$this->persistenceManager->persistAll();
		}

		$this->view->assign('value', array('data' => array('collectionContent' => $result, 'nodePath' => $newNode->getContextPath())));
	}

	/**
	 * Creates a new node and returns tree structure
	 *
	 * @param Node $referenceNode
	 * @param array $nodeData
	 * @param string $position where the node should be added, -1 is before, 0 is in, 1 is after
	 * @return void
	 */
	public function createNodeForTheTreeAction(Node $referenceNode, array $nodeData, $position) {
		$newNode = $this->nodeOperations->create($referenceNode, $nodeData, $position);
		$this->view->assign('value', array('data' => $this->view->collectTreeNodeData($newNode), 'success' => TRUE));
	}

	/**
	 * Move $node before, into or after $targetNode
	 *
	 * We need to call persistAll() in order to return the nextUri. We can't persist only the nodes in NodeDataRepository
	 * because they might be connected to images / resources which need to be updated at the same time.
	 *
	 * @param Node $node The node to be moved
	 * @param Node $targetNode The target node to be moved "to", see $position
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return void
	 */
	public function moveAction(Node $node, Node $targetNode, $position) {
		$node = $this->nodeOperations->move($node, $targetNode, $position);

		if ($this->request->getHttpRequest()->isMethodSafe() === FALSE) {
			$this->persistenceManager->persistAll();
		}

		$data = array('newNodePath' => $node->getContextPath());
		if ($node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			$data['nextUri'] = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos');
		}
		$this->view->assign('value', array('data' => $data, 'success' => TRUE));
	}

	/**
	 * Move the given node before, into or after the target node depending on the given position and renders it's content collection.
	 *
	 * @param Node $node The node to be moved
	 * @param Node $targetNode The target node to be moved "to", see $position
	 * @param string $position Where the node should be added in relation to $targetNode (allowed: before, into, after)
	 * @param string $typoScriptPath The TypoScript path of the collection
	 * @return void
	 */
	public function moveAndRenderAction(Node $node, Node $targetNode, $position, $typoScriptPath) {
		$node = $this->nodeOperations->move($node, $targetNode, $position);

		$q = new FlowQuery(array($targetNode));
		$closestContentCollection = $q->closest('[instanceof TYPO3.Neos:ContentCollection]')->get(0);
		$result = $this->renderNode($closestContentCollection, $typoScriptPath);

		// TODO: For some reason persistAll BEFORE rendering will break images (probably something in doctrine), so do not change nodes, persist and render. If change and render happens, persist afterwards.
		if ($this->request->getHttpRequest()->isMethodSafe() === FALSE) {
			$this->persistenceManager->persistAll();
		}

		$this->view->assign('value', array('data' => array('collectionContent' => $result, 'nodePath' => $node->getContextPath()), 'success' => TRUE));
	}

	/**
	 * Copy $node before, into or after $targetNode
	 *
	 * We need to call persistAll() in order to return the nextUri. We can't persist only the nodes in NodeDataRepository
	 * because they might be connected to images / resources which need to be updated at the same time.
	 *
	 * @param Node $node The node to be copied
	 * @param Node $targetNode The target node to be copied "to", see $position
	 * @param string $position Where the node should be added in relation to $targetNode (allowed: before, into, after)
	 * @param string $nodeName Optional node name (if empty random node name will be generated)
	 * @return void
	 * @throws NodeException
	 */
	public function copyAction(Node $node, Node $targetNode, $position, $nodeName = NULL) {
		$copiedNode = $this->nodeOperations->copy($node, $targetNode, $position, $nodeName);

		if ($this->request->getHttpRequest()->isMethodSafe() === FALSE) {
			$this->persistenceManager->persistAll();
		}

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
	 * Copies the given node before, into or after the target node depending on the given position and renders it's content collection.
	 *
	 * @param Node $node The node to be copied
	 * @param Node $targetNode The target node to be copied "to", see $position
	 * @param string $position Where the node should be added in relation to $targetNode (allowed: before, into, after)
	 * @param string $nodeName Optional node name (if empty random node name will be generated)
	 * @param string $typoScriptPath The TypoScript path of the collection
	 * @return void
	 */
	public function copyAndRenderAction(Node $node, Node $targetNode, $position, $typoScriptPath, $nodeName = NULL) {
		$copiedNode = $this->nodeOperations->copy($node, $targetNode, $position, $nodeName);

		$q = new FlowQuery(array($targetNode));
		$closestContentCollection = $q->closest('[instanceof TYPO3.Neos:ContentCollection]')->get(0);
		$result = $this->renderNode($closestContentCollection, $typoScriptPath);

		// TODO: For some reason persistAll BEFORE rendering will break images (probably something in doctrine), so do not change nodes, persist and render. If change and render happens, persist afterwards.
		if ($this->request->getHttpRequest()->isMethodSafe() === FALSE) {
			$this->persistenceManager->persistAll();
		}

		$this->view->assign('value', array('data' => array('collectionContent' => $result, 'nodePath' => $copiedNode->getContextPath()), 'success' => TRUE));
	}

	/**
	 * Updates the specified node.
	 *
	 * Returns the following data:
	 *
	 * - the (possibly changed) workspace name of the node
	 * - the URI of the closest document node. If $node is a document node (f.e. a Page), the own URI is returned.
	 *   This is important to handle renames of nodes correctly.
	 *
	 * Note: We do not call $nodeDataRepository->update() here, as TYPO3CR has a stateful API for now.
	 *       We need to call persistAll() in order to return the nextUri. We can't persist only the nodes in NodeDataRepository
	 *       because they might be connected to images / resources which need to be updated at the same time.
	 *
	 * @param Node $node The node to be updated
	 * @return void
	 */
	public function updateAction(Node $node) {
		if ($this->request->getHttpRequest()->isMethodSafe() === FALSE) {
			$this->persistenceManager->persistAll();
		}

		$q = new FlowQuery(array($node));
		$closestDocumentNode = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $closestDocumentNode), 'Frontend\Node', 'TYPO3.Neos');
		$this->view->assign('value', array('data' => array('workspaceNameOfNode' => $node->getWorkspace()->getName(), 'nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Updates the specified node and renders it's content collection.
	 *
	 * @param Node $node The node to be updated
	 * @param string $typoScriptPath The TypoScript path of the collection
	 * @return void
	 */
	public function updateAndRenderAction(Node $node, $typoScriptPath) {
		$q = new FlowQuery(array($node));
		$closestContentCollection = $q->closest('[instanceof TYPO3.Neos:ContentCollection]')->get(0);
		$result = $this->renderNode($closestContentCollection, $typoScriptPath);

		// TODO: For some reason persistAll BEFORE rendering will break images (probably something in doctrine), so do not change nodes, persist and render. If change and render happens, persist afterwards.
		if ($this->request->getHttpRequest()->isMethodSafe() === FALSE) {
			$this->persistenceManager->persistAll();
		}

		$this->view->assign('value', array('data' => array('collectionContent' => $result, 'nodePath' => $node->getContextPath()), 'workspaceNameOfNode' => $node->getWorkspace()->getName(), 'success' => TRUE));
	}

	/**
	 * Deletes the specified node and all of its sub nodes
	 *
	 * We need to call persistAll() in order to return the nextUri. We can't persist only the nodes in NodeDataRepository
	 * because they might be connected to images / resources which need to be removed at the same time.
	 *
	 * @param Node $node
	 * @return void
	 */
	public function deleteAction(Node $node) {
		if ($this->request->getHttpRequest()->isMethodSafe() === FALSE) {
			$this->persistenceManager->persistAll();
		}

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
	 * @param NodeInterface $node
	 * @param string $typoScriptPath
	 * @return string
	 */
	protected function renderNode($node, $typoScriptPath) {
		$view = new TypoScriptView();
		$this->controllerContext->getRequest()->setFormat('html');
		$view->setControllerContext($this->controllerContext);
		$view->setOption('enableContentCache', FALSE);

		$view->setTypoScriptPath($typoScriptPath);
		$view->assign('value', $node);
		return $view->render();
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
}
