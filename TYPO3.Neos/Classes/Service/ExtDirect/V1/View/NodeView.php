<?php
namespace TYPO3\TYPO3\Service\ExtDirect\V1\View;

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

/**
 * An ExtDirect View specialized on single or multiple Nodes
 *
 * @FLOW3\Scope("prototype")
 */
class NodeView extends \TYPO3\ExtJS\ExtDirect\View {

	const LISTSTYLE = 1;
	const TREESTYLE = 2;

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
	 * @param integer $outputStyle Either TREESTYLE or LISTSTYLE
	 * @param integer $depth How many levels of childNodes (0 = unlimited)
	 * @return void
	 */
	public function assignChildNodes(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $contentTypeFilter, $outputStyle = self::LISTSTYLE, $depth = 0) {
		$this->outputStyle = $outputStyle;
		$nodes = array();
		$this->collectChildNodeData($nodes, $node, ($contentTypeFilter === '' ? NULL : $contentTypeFilter), $depth);

		$this->setConfiguration(array('value' => array('data' => array('_descendAll' => array()))));

		$this->assign('value', array('data' => $nodes, 'success' => TRUE));
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
		$uriBuilder = $this->controllerContext->getUriBuilder();
		foreach ($node->getChildNodes($contentTypeFilter) as $childNode) {
			$contextNodePath = $childNode->getContextPath();
			$workspaceName = $childNode->getWorkspace()->getName();
			$nodeName = $childNode->getName();
			$contentType = $childNode->getContentType()->getName();
			$title = $childNode->getContentType() === 'TYPO3.TYPO3:Page' ? $childNode->getProperty('title'): $childNode->getLabel();
			$abstract = $childNode->getAbstract();
			$expand = ($depth === 0 || $recursionPointer < $depth);
			switch ($this->outputStyle) {
				case self::LISTSTYLE:
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
				case self::TREESTYLE:
					$uriForNode = $uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $childNode), 'Frontend\Node', 'TYPO3.TYPO3', '');
					$hasChildNodes = $childNode->hasChildNodes($contentTypeFilter);

					$treeNode = array(
						'key' => $contextNodePath,
						'title' => $title,
						'href' => $uriForNode,
						'isFolder' => $hasChildNodes,
						'isLazy' => ($hasChildNodes && !$expand),
						'contentType' => $contentType,
						'expand' => $expand
					);

					if ($expand && $hasChildNodes === TRUE) {
						$children = array();
						$this->collectChildNodeData($children, $childNode, $contentTypeFilter, $depth, ($recursionPointer + 1));
						if ($children !== array()) {
							$treeNode['children'] = $children;
						}
					}

					array_push($nodes, $treeNode);
			}
		}
	}

}
?>