<?php
namespace TYPO3\TYPO3CR\Migration\Filters;

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
 * Filter nodes having the given property and its value not empty.
 */
class PropertyNotEmpty implements FilterInterface {

	/**
	 * The name of the property to be checked for non-empty value.
	 *
	 * @var string
	 */
	protected $propertyName;

	/**
	 * Sets the property name to be checked for non-empty value.
	 *
	 * @param string $propertyName
	 * @return void
	 */
	public function setPropertyName($propertyName) {
		$this->propertyName = $propertyName;
	}

	/**
	 * Returns TRUE if the given node has the property and the value is not empty.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return boolean
	 */
	public function matches(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		if ($node->hasProperty($this->propertyName)) {
			$propertyValue = $node->getProperty($this->propertyName);
			return !empty($propertyValue);
		}
		return FALSE;
	}

}
