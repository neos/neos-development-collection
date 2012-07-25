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
	 * Prepares this view to render the specified list of nodes
	 *
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes The nodes to render
	 * @return void
	 */
	public function assignNodes(array $nodes) {
		$data = array();
		$propertyNames = array();

		foreach ($nodes as $node) {
			$this->collectNodeData($data, $propertyNames, $node);
		}

		$this->setConfiguration(array('value' => array('data' => array('_descendAll' => array()))));
		$this->assign('value',
			array(
				'data' => $data,
				'metaData' => array(
					'idProperty' => '__nodePath',
					'root' => 'data',
					'fields' => array_keys($propertyNames)
				),
				'success' => TRUE
			)
		);
	}


	/**
	 * Prepares this view to render a list or tree of child nodes of the given node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node The node to fetch child nodes of
	 * @param string $contentTypeFilter Criteria for filtering the child nodes
	 * @param integer $outputStyle Either TREESTYLE or LISTSTYLE
	 * @param integer $depth how many levels of childNodes (0 = unlimited)
	 * @return void
	 */
	public function assignChildNodes(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $contentTypeFilter, $outputStyle = self::LISTSTYLE, $depth = 0) {
		$contentTypeFilter = ($contentTypeFilter === '' ? NULL : $contentTypeFilter);
		$metaData = array();
		$data = array();

		$uriBuilder = $this->controllerContext->getUriBuilder();
		switch ($outputStyle) {
			case self::TREESTYLE :
				foreach ($node->getChildNodes($contentTypeFilter) as $childNode) {
					$uriForNode = $uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $childNode), 'Frontend\Node', 'TYPO3.TYPO3', '');
					$hasChildNodes = $childNode->hasChildNodes($contentTypeFilter);

					$data[] = array(
						'key' => $childNode->getContextPath(),
							// TODO Move to JS
						'title' => $childNode->getContentType() === 'TYPO3.TYPO3:Page' ? $childNode->getProperty('title'): $childNode->getLabel(),
						'href' => $uriForNode,
						'isFolder' => $hasChildNodes,
						'isLazy' => $hasChildNodes,
						'contentType' => $childNode->getContentType()
					);
				}
			break;

			case self::LISTSTYLE :
				$propertyNames = array();
				$this->collectChildNodeData($data, $propertyNames, $node, $contentTypeFilter, $depth);
				$metaData = array(
					'idProperty' => '__nodePath',
					'root' => 'data',
					'fields' => array_keys($propertyNames)
				);
		}

		$this->setConfiguration(array('value' => array('data' => array('_descendAll' => array()))));
		$value = array('data' => $data, 'success' => TRUE);
		if ($metaData !== array()) {
			$value['metaData'] = $metaData;
		}

		$this->assign('value', $value);
	}

	/**
	 * Collect node data and recurse into child nodes
	 *
	 * @param array &$data
	 * @param array &$propertyNames
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $contentTypeFilter
	 * @param integer $depth levels of child nodes to fetch. 0 = unlimited
	 * @param integer $recursionPointer current recursion level
	 * @return void
	 */
	protected function collectChildNodeData(array &$data, array &$propertyNames, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $contentTypeFilter, $depth = 0, $recursionPointer = 1) {
		foreach ($node->getChildNodes($contentTypeFilter) as $childNode) {
			$this->collectNodeData($data, $propertyNames, $childNode);
			if ($depth === 0 || ($recursionPointer < $depth)) {
				$this->collectChildNodeData($data, $propertyNames, $childNode, $contentTypeFilter, $depth, ($recursionPointer + 1));
			}
		}
	}

	/**
	 * Collects node data of the given node
	 *
	 * @param array &$data
	 * @param array &$propertyNames
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	protected function collectNodeData(array &$data, array &$propertyNames, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$properties = $node->getProperties();
		$properties['__contextNodePath'] = $node->getContextPath();
		$properties['__workspaceName'] = $node->getWorkspace()->getName();
		$properties['__nodeName'] = $node->getName();
		$properties['__contentType'] = $node->getContentType();
		$properties['__label'] = $node->getLabel();
		$properties['__abstract'] = $node->getAbstract();
		$data[] = $properties;

		foreach ($properties as $propertyName => $propertyValue) {
			$propertyNames[$propertyName] = TRUE;
		}

	}
}
?>