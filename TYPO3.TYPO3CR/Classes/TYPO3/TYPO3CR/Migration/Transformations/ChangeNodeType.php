<?php
namespace TYPO3\TYPO3CR\Migration\Transformations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Change the node type.
 */
class ChangeNodeType extends AbstractTransformation {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * The new Node Type to use as a string
	 *
	 * @var string
	 */
	protected $newType;

	/**
	 * @param string $newType
	 * @return void
	 */
	public function setNewType($newType) {
		$this->newType = $newType;
	}

	/**
	 * If the given node has the property this transformation should work on, this
	 * returns TRUE if the given NodeType is registered with the NodeTypeManager
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return boolean
	 */
	public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		return $this->nodeTypeManager->hasNodeType($this->newType);
	}

	/**
	 * Change the Node Type on the given node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$nodeType = $this->nodeTypeManager->getNodeType($this->newType);
		$node->setNodeType($nodeType);
	}
}
?>