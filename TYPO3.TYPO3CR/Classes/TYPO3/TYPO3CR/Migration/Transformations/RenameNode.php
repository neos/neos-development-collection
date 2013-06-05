<?php
namespace TYPO3\TYPO3CR\Migration\Transformations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Rename a given node.
 */
class RenameNode extends AbstractTransformation {

	/**
	 * New name of node
	 *
	 * @var string
	 */
	protected $newName;

	/**
	 * Sets the new name for the node to change.
	 *
	 * @param string $newName
	 * @return void
	 */
	public function setNewName($newName) {
		$this->newName = $newName;
	}

	/**
	 * Returns TRUE if the given node does not yet have the new name.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return boolean
	 */
	public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		return ($node->getName() !== $this->newName);
	}

	/**
	 * Renames the node to the new name.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$node->setName($this->newName);
	}
}
?>