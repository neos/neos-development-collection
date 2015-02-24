<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * An Eel context matching expression for the node privileges
 *
 * @Flow\Proxy(false)
 */
class NodePrivilegeContext {

	/**
	 * @var NodeInterface
	 */
	protected $node;

	/**
	 * @param NodeInterface $node
	 */
	function __construct(NodeInterface $node = NULL) {
		$this->node = $node;
	}

	/**
	 * @param NodeInterface $node
	 * @return void
	 */
	public function setNode(NodeInterface $node) {
		$this->node = $node;
	}

	/**
	 * @param string $nodePath
	 * @return boolean
	 */
	public function isDescendantNodeOf($nodePath) {
		if ($this->node === NULL) {
			return TRUE;
		}
		$testedNodePath = rtrim($this->node->getPath(), '/') . '/';
		return substr($testedNodePath, 0, strlen($nodePath)) === $nodePath;
	}

	/**
	 * @param string|array $nodeTypes
	 * @return boolean
	 */
	public function nodeIsOfType($nodeTypes) {
		if ($this->node === NULL) {
			return TRUE;
		}
		if (!is_array($nodeTypes)) {
			$nodeTypes = array($nodeTypes);
		}
		foreach ($nodeTypes as $nodeType) {
			if ($this->node->getNodeType()->isOfType($nodeType)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * @param string|array $workspaceNames
	 * @return boolean
	 */
	public function isInWorkspace($workspaceNames) {
		if ($this->node === NULL) {
			return TRUE;
		}

		return in_array($this->node->getWorkspace()->getName(), $workspaceNames);
	}
}