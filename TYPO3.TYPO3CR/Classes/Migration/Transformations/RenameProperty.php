<?php
namespace TYPO3\TYPO3CR\Migration\Transformations;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Rename a given property.
 */
class RenameProperty extends AbstractTransformation {

	/**
	 * Property name to change
	 *
	 * @var string
	 */
	protected $oldPropertyName;

	/**
	 * New name of property
	 *
	 * @var string
	 */
	protected $newPropertyName;

	/**
	 * Sets the name of the property to change.
	 *
	 * @param string $oldPropertyName
	 * @return void
	 */
	public function setFrom($oldPropertyName) {
		$this->oldPropertyName = $oldPropertyName;
	}

	/**
	 * Sets the new name for the property to change.
	 *
	 * @param string $newPropertyName
	 * @return void
	 */
	public function setTo($newPropertyName) {
		$this->newPropertyName = $newPropertyName;
	}

	/**
	 * Returns TRUE if the given node has a property with the name to work on
	 * and does not yet have a property with the name to rename that property to.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return boolean
	 */
	public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		return ($node->hasProperty($this->oldPropertyName) && !$node->hasProperty($this->newPropertyName));
	}

	/**
	 * Renames the configured property to the new name.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$node->setProperty($this->newPropertyName, $node->getProperty($this->oldPropertyName));
		$node->removeProperty($this->oldPropertyName);
	}
}
?>