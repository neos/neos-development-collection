<?php
namespace TYPO3\Neos\Security\Authorization\Privilege;

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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * An Eel context matching expression for the NodeTreePrivilege
 *
 * @Flow\Proxy(false)
 */
class NodeTreePrivilegeContext {

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
		$nodePath = rtrim($nodePath, '/') . '/';
		$testedNodePath = rtrim($this->node->getPath(), '/') . '/';
		return substr($testedNodePath, 0, strlen($nodePath)) === $nodePath;
	}
}