<?php
namespace TYPO3\Neos\Service\View;

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
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Flow\Utility\Arrays;

/**
 * An View specialized on single or multiple Nodes in a tree structure
 *
 * @Flow\Scope("prototype")
 */
class NodeView extends \TYPO3\Flow\Mvc\View\JsonView {

	/**
	 * @var integer
	 */
	const STYLE_LIST = 1;
	const STYLE_TREE = 2;

	/**
	 * @var integer
	 */
	protected $outputStyle;

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Assigns a node to the NodeView.
	 *
	 * @param NodeInterface $node The node to render
	 * @param array $propertyNames Optional list of property names to include in the JSON output
	 * @return void
	 */
	public function assignNode(NodeInterface $node, array $propertyNames = array('name', 'path', 'identifier', 'properties', 'nodeType')) {
		$this->setConfiguration(
			array(
				'value' => array(
					'data' => array(
						'_only' => array('name', 'path', 'identifier', 'properties', 'nodeType'),
						'_descend' => array('properties' => $propertyNames)
					)
				)
			)
		);
		$this->assign('value', array('data' => $node, 'success' => TRUE));
	}

	/**
	 * @param array $nodes
	 */
	public function assignNodes(array $nodes) {
		$data = array();
		foreach ($nodes as $node) {
			if ($node->getPath() !== '/') {
				$q = new FlowQuery(array($node));
				$closestDocumentNode = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
				if ($closestDocumentNode !== NULL) {
					$data[] = array(
						'nodePath' => $node->getPath(),
						'pageNodePath' => $closestDocumentNode->getPath(),
					);
				} else {
					$this->systemLogger->log('You have a node that is no longer connected to a parent. Path: ' . $node->getPath() . ' (Identifier: ' . $node->getIdentifier() . ')');
				}
			}
		}

		$this->assign('value', array('data' => $data, 'success' => TRUE));
	}

	/**
	 * Prepares this view to render a list or tree of child nodes of the given node.
	 *
	 * @param NodeInterface $node The node to fetch child nodes of
	 * @param string $nodeTypeFilter Criteria for filtering the child nodes
	 * @param integer $outputStyle Either STYLE_TREE or STYLE_list
	 * @param integer $depth How many levels of childNodes (0 = unlimited)
	 * @param NodeInterface $untilNode if given, expand all nodes on the rootline towards $untilNode, no matter what is defined with $depth.
	 * @return void
	 */
	public function assignChildNodes(NodeInterface $node, $nodeTypeFilter, $outputStyle = self::STYLE_LIST, $depth = 0, NodeInterface $untilNode = NULL) {
		$this->outputStyle = $outputStyle;
		$nodes = array();
		$this->collectChildNodeData($nodes, $node, ($nodeTypeFilter === '' ? NULL : $nodeTypeFilter), $depth, $untilNode);
		$this->setConfiguration(array('value' => array('data' => array('_descendAll' => array()))));

		$this->assign('value', array('data' => $nodes, 'success' => TRUE));
	}

	/**
	 * Prepares this view to render a list or tree of filtered nodes.
	 *
	 * @param NodeInterface $node
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeData> $matchedNodes
	 * @param integer $outputStyle Either STYLE_TREE or STYLE_list
	 * @return void
	 */
	public function assignFilteredChildNodes(NodeInterface $node, array $matchedNodes, $outputStyle = self::STYLE_LIST) {
		$this->outputStyle = $outputStyle;
		$nodes = $this->collectParentNodeData($node, $matchedNodes);
		$this->setConfiguration(array('value' => array('data' => array('_descendAll' => array()))));

		$this->assign('value', array('data' => $nodes, 'success' => TRUE));
	}

	/**
	 * Collect node data for this node
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	public function assignOneNodeForTree(NodeInterface $node) {
		$uriBuilder = $this->controllerContext->getUriBuilder();

		$contextNodePath = $node->getContextPath();
		$nodeType = $node->getNodeType()->getName();
		$title = $nodeType === 'TYPO3.Neos:Document' ? $node->getProperty('title'): $node->getLabel();
		$expand = 0;
		if ($node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			$uriForNode = $uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos');
		} else {
			$uriForNode = '#';
		}

		$treeNode = array(
			'key' => $contextNodePath,
			'title' => $title,
			'href' => $uriForNode,
			'isFolder' => 0,
			'isLazy' => 0,
			'nodeType' => $nodeType,
			'expand' => $expand,
			'addClass' => strtolower(str_replace(array('.', ':'), array('_', '-'), $nodeType))
		);

		$this->assign('value', array('data' => $treeNode, 'success' => TRUE));
	}

	/**
	 * Collect node data and traverse child nodes
	 *
	 * @param array &$nodes
	 * @param NodeInterface $node
	 * @param string $nodeTypeFilter
	 * @param integer $depth levels of child nodes to fetch. 0 = unlimited
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $untilNode if given, expand all nodes on the rootline towards $untilNode, no matter what is defined with $depth.
	 * @param integer $recursionPointer current recursion level
	 * @return void
	 */
	protected function collectChildNodeData(array &$nodes, NodeInterface $node, $nodeTypeFilter, $depth = 0, NodeInterface $untilNode = NULL, $recursionPointer = 1) {
		foreach ($node->getChildNodes($nodeTypeFilter) as $childNode) {
			/** @var NodeInterface $childNode */
			$expand = ($depth === 0 || $recursionPointer < $depth);

			if ($expand === FALSE && $untilNode !== NULL && strpos($untilNode->getPath(), $childNode->getPath()) === 0 && $childNode !== $untilNode) {
				// in case $untilNode is set, and the current childNode is on the rootline of $untilNode (and not the node itself), expand the node.
				$expand = TRUE;
			}

			switch ($this->outputStyle) {
				case self::STYLE_LIST:
					$nodeType = $childNode->getNodeType()->getName();
					$properties = $childNode->getProperties();
					$properties['__contextNodePath'] = $childNode->getContextPath();
					$properties['__workspaceName'] = $childNode->getWorkspace()->getName();
					$properties['__nodeName'] = $childNode->getName();
					$properties['__nodeType'] = $nodeType;
					$properties['__title'] = $nodeType === 'TYPO3.Neos:Document' ? $childNode->getProperty('title') : $childNode->getLabel();
					array_push($nodes, $properties);
					if ($expand) {
						$this->collectChildNodeData($nodes, $childNode, $nodeTypeFilter, $depth, $untilNode, ($recursionPointer + 1));
					}
				break;
				case self::STYLE_TREE:
					$children = array();
					$hasChildNodes = $childNode->hasChildNodes($nodeTypeFilter) === TRUE;
					if ($expand && $hasChildNodes) {
						$this->collectChildNodeData($children, $childNode, $nodeTypeFilter, $depth, $untilNode, ($recursionPointer + 1));
					}
					array_push($nodes, $this->collectTreeNodeData($childNode, $expand, $children, $hasChildNodes));
			}
		}
	}

	/**
	 * @param NodeInterface $rootNode
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeData> $nodes
	 * @return array
	 */
	public function collectParentNodeData(NodeInterface $rootNode, array $nodes) {
		$nodeCollection = array();

		$addNode = function($node, $matched) use($rootNode, &$nodeCollection) {
			/** @var NodeInterface $node */
			$path = str_replace('/', '.children.', substr($node->getPath(), strlen($rootNode->getPath()) + 1));
			if ($path !== '') {
				$nodeCollection = Arrays::setValueByPath($nodeCollection, $path . '.node', $node);
				if ($matched === TRUE) {
					$nodeCollection = Arrays::setValueByPath($nodeCollection, $path . '.matched', TRUE);
				}
			}
		};

		$findParent = function($node) use(&$findParent, &$addNode) {
			/** @var NodeInterface $node */
			$parent = $node->getParent();
			if ($parent !== NULL) {
				$addNode($parent, FALSE);
				$findParent($parent);
			}
		};

		foreach ($nodes as $node) {
			$addNode($node, TRUE);
			$findParent($node);
		}

		$treeNodes = array();
		$collectTreeNodeData = function(&$treeNodes, $node) use(&$collectTreeNodeData) {
			$children = array();
			if (isset($node['children'])) {
				foreach ($node['children'] as $childNode) {
					$collectTreeNodeData($children, $childNode);
				}
			}
			$treeNodes[] = $this->collectTreeNodeData($node['node'], TRUE, $children, $children !== array(), isset($node['matched']));
		};

		foreach ($nodeCollection as $firstLevelNode) {
			$collectTreeNodeData($treeNodes, $firstLevelNode);
		}

		return $treeNodes;
	}

	/**
	 * @param NodeInterface $node
	 * @param boolean $expand
	 * @param array $children
	 * @param boolean $hasChildNodes
	 * @param boolean $matched
	 * @return array
	 */
	public function collectTreeNodeData(NodeInterface $node, $expand = TRUE, array $children = array(), $hasChildNodes = FALSE, $matched = FALSE) {
		$isTimedPage = FALSE;
		$now = new \DateTime();
		$now = $now->getTimestamp();
		$hiddenBeforeDateTime = $node->getHiddenBeforeDateTime();
		$hiddenAfterDateTime = $node->getHiddenAfterDateTime();

		if ($hiddenBeforeDateTime !== NULL && $hiddenBeforeDateTime->getTimestamp() > $now) {
			$isTimedPage = TRUE;
		}
		if ($hiddenAfterDateTime !== NULL) {
			$isTimedPage = TRUE;
		}

		$classes = array();
		if ($isTimedPage === TRUE && $node->isHidden() === FALSE) {
			array_push($classes, 'neos-timedVisibility');
		}
		if ($node->isHidden() === TRUE) {
			array_push($classes, 'neos-hidden');
		}
		if ($node->isHiddenInIndex() === TRUE) {
			array_push($classes, 'neos-hiddenInIndex');
		}
		if ($matched) {
			array_push($classes, 'neos-matched');
		}

		$uriBuilder = $this->controllerContext->getUriBuilder();
		$nodeType = $node->getNodeType();
		$nodeTypeConfiguration = $nodeType->getFullConfiguration();
		if ($node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			$uriForNode = $uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos');
		} else {
			$uriForNode = '#';
		}
		$treeNode = array(
			'key' => $node->getContextPath(),
			'title' => $node->getLabel(),
			'tooltip' => $node->getFullLabel(),
			'href' => $uriForNode,
			'isFolder' => $hasChildNodes,
			'isLazy' => ($hasChildNodes && !$expand),
			'nodeType' => $nodeType->getName(),
			'expand' => $expand,
			'addClass' => implode(' ', $classes),
			'name' => $node->getName(),
			'iconClass' => isset($nodeTypeConfiguration['ui']) && isset($nodeTypeConfiguration['ui']['icon']) ? $nodeTypeConfiguration['ui']['icon'] : '',
			'isHidden' => $node->isHidden()
		);
		if ($hasChildNodes) {
			$treeNode['children'] = $children;
		}
		return $treeNode;
	}
}