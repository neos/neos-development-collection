<?php
namespace TYPO3\Neos\Service\ExtDirect\V1\View;

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

/**
 * An ExtDirect View specialized on single or multiple Nodes
 *
 * @Flow\Scope("prototype")
 */
class NodeView extends \TYPO3\ExtJS\ExtDirect\View {

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
	 * Assigns a node to the NodeView.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node The node to render
	 * @param array $propertyNames Optional list of property names to include in the JSON output
	 * @return void
	 */
	public function assignNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, array $propertyNames = array('name', 'path', 'identifier', 'properties', 'contentType')) {
		$this->setConfiguration(
			array(
				'value' => array(
					'data' => array(
						'_only' => array('name', 'path', 'identifier', 'properties', 'contentType'),
						'_descend' => array('properties' => $propertyNames)
					)
				)
			)
		);
		$this->assign('value', array('data' => $node, 'success' => TRUE));
	}

	/**
	 * Prepares this view to render a list or tree of child nodes of the given node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node The node to fetch child nodes of
	 * @param string $contentTypeFilter Criteria for filtering the child nodes
	 * @param integer $outputStyle Either STYLE_TREE or STYLE_list
	 * @param integer $depth How many levels of childNodes (0 = unlimited)
	 * @return void
	 */
	public function assignChildNodes(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $contentTypeFilter, $outputStyle = self::STYLE_LIST, $depth = 0) {
		$this->outputStyle = $outputStyle;
		$nodes = array();
		$this->collectChildNodeData($nodes, $node, ($contentTypeFilter === '' ? NULL : $contentTypeFilter), $depth);
		$this->setConfiguration(array('value' => array('data' => array('_descendAll' => array()))));

		$this->assign('value', array('data' => $nodes, 'success' => TRUE));
	}

	/**
	 * Collect node data for this node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function assignOneNodeForTree(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$uriBuilder = $this->controllerContext->getUriBuilder();

		$contextNodePath = $node->getContextPath();
		$contentType = $node->getContentType()->getName();
		$title = $node->getContentType() === 'TYPO3.Neos.ContentTypes:Page' ? $node->getProperty('title'): $node->getLabel();
		$expand = 0;
		$uriForNode = $uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos', '');

		$treeNode = array(
			'key' => $contextNodePath,
			'title' => $title,
			'href' => $uriForNode,
			'isFolder' => 0,
			'isLazy' => 0,
			'contentType' => $contentType,
			'expand' => $expand,
			'addClass' => strtolower(str_replace(array('.', ':'), array('_', '-'), $contentType))
		);

		$this->assign('value', array('data' => $treeNode, 'success' => TRUE));
	}

	/**
	 * Collect node data and recurse into child nodes
	 *
	 * @param array &$nodes
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $contentTypeFilter
	 * @param integer $depth levels of child nodes to fetch. 0 = unlimited
	 * @param integer $recursionPointer current recursion level
	 * @return void
	 */
	protected function collectChildNodeData(array &$nodes, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $contentTypeFilter, $depth = 0, $recursionPointer = 1) {
		foreach ($node->getChildNodes($contentTypeFilter) as $childNode) {
			$contextNodePath = $childNode->getContextPath();
			$workspaceName = $childNode->getWorkspace()->getName();
			$nodeName = $childNode->getName();
			$contentType = $childNode->getContentType()->getName();
			$title = $childNode->getContentType() === 'TYPO3.Neos.ContentTypes:Page' ? $childNode->getProperty('title'): $childNode->getLabel();
			$abstract = $childNode->getAbstract();
			$expand = ($depth === 0 || $recursionPointer < $depth);
			switch ($this->outputStyle) {
				case self::STYLE_LIST:
					$properties = $childNode->getProperties();
					$properties['__contextNodePath'] = $contextNodePath;
					$properties['__workspaceName'] = $workspaceName;
					$properties['__nodeName'] = $nodeName;
					$properties['__contentType'] = $contentType;
					$properties['__title'] = $title;
					$properties['__abstract'] = $abstract;
					array_push($nodes, $properties);
					if ($expand) {
						$this->collectChildNodeData($nodes, $childNode, $contentTypeFilter, $depth, ($recursionPointer + 1));
					}
				break;
				case self::STYLE_TREE:
					$children = array();
					if ($expand && $childNode->hasChildNodes($contentTypeFilter) === TRUE) {
						$this->collectChildNodeData($children, $childNode, $contentTypeFilter, $depth, ($recursionPointer + 1));
					}
					array_push($nodes, $this->collectTreeNodeData($childNode, $expand, $children));
			}
		}
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param boolean $expand
	 * @param array $children
	 * @return array
	 */
	public function collectTreeNodeData(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $expand = TRUE, array $children = array()) {
		$contentType = $node->getContentType()->getName();
		$classes = array(strtolower(str_replace(array('.', ':'), array('_', '-'), $contentType)));
		if ($node->isHidden() === TRUE) {
			array_push($classes, 'hidden');
		}
		if ($node->isHiddenInIndex() === TRUE) {
			array_push($classes, 'hiddenInIndex');
		}

		$uriBuilder = $this->controllerContext->getUriBuilder();
		$hasChildNodes = $children !== array() ? TRUE : FALSE;
		$contentType = $node->getContentType()->getName();
		$treeNode = array(
			'key' => $node->getContextPath(),
			'title' => $node->getContentType() === 'TYPO3.Neos.ContentTypes:Page' ? $node->getProperty('title') : $node->getLabel(),
			'href' => $uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos', ''),
			'isFolder' => $hasChildNodes,
			'isLazy' => ($hasChildNodes && !$expand),
			'contentType' => $contentType,
			'expand' => $expand,
			'addClass' => implode(' ', $classes),
			'name' => $node->getName()
		);
		if ($hasChildNodes) {
			$treeNode['children'] = $children;
		}
		return $treeNode;
	}

}
?>