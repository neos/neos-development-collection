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
 * Change the value of a given property.
 */
class ChangePropertyValue extends AbstractTransformation {

	/**
	 * @var string
	 */
	protected $propertyName;

	/**
	 * @var string
	 */
	protected $newValue;

	/**
	 * Placeholder for the current property value to be inserted in newValue.
	 *
	 * @var string
	 */
	protected $currentValuePlaceholder = '{current}';

	/**
	 * Sets the name of the property to change.
	 *
	 * @param string $propertyName
	 * @return void
	 */
	public function setProperty($propertyName) {
		$this->propertyName = $propertyName;
	}

	/**
	 * New property value to be set.
	 *
	 * The value of the option "currentValuePlaceholder" (defaults to "{current}") will be
	 * used to include the current property value into the new value.
	 *
	 * @param string $newValue
	 * @return void
	 */
	public function setNewValue($newValue) {
		$this->newValue = $newValue;
	}

	/**
	 * The value of this option (defaults to "{current}") will be used to include the
	 * current property value into the new value.
	 *
	 * @param string $currentValuePlaceholder
	 * @return void
	 */
	public function setCurrentValuePlaceholder($currentValuePlaceholder) {
		$this->currentValuePlaceholder = $currentValuePlaceholder;
	}

	/**
	 * If the given node has the property this transformation should work on, this
	 * returns TRUE.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return boolean
	 */
	public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		return ($node->hasProperty($this->propertyName));
	}

	/**
	 * Change the property on the given node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$currentPropertyValue = $node->getProperty($this->propertyName);
		$newValueWithReplacedCurrentValue = str_replace($this->currentValuePlaceholder, $currentPropertyValue, $this->newValue);
		$node->setProperty($this->propertyName, $newValueWithReplacedCurrentValue);
	}
}
?>