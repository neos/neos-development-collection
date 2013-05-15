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
 * Add the new property and its value
 */
class AddNewProperty extends AbstractTransformation {

	/**
	 * @var string
	 */
	protected $newPropertyName;

	/**
	 * @var string
	 */
	protected $value;

	/**
	 * Sets the name of the new property to be added.
	 *
	 * @param string $newPropertyName
	 * @return void
	 */
	public function setNewPropertyName($newPropertyName) {
		$this->newPropertyName = $newPropertyName;
	}

	/**
	 * Property value to be set.
	 *
	 * @param string $value
	 * @return void
	 */
	public function setValue($value) {
		$this->value = $value;
	}

	/**
	 * If the given node has no property this transformation should work on, this
	 * returns TRUE.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return boolean
	 */
	public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		return !$node->hasProperty($this->newPropertyName);
	}

	/**
	 * Add the new property with the given value on the given node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return void
	 */
	public function execute(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		$node->setProperty($this->newPropertyName, $this->value);
	}
}
?>