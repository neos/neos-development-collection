<?php
namespace TYPO3\TYPO3CR\Migration\Transformations;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Strip all tags on a given property
 */
class StripTagsOnProperty extends AbstractTransformation {

	/**
	 * Property name to change
	 *
	 * @var string
	 */
	protected $propertyName;

	/**
	 * Sets the name of the property to work on.
	 *
	 * @param string $propertyName
	 * @return void
	 */
	public function setProperty($propertyName) {
		$this->propertyName = $propertyName;
	}

	/**
	 * Returns TRUE if the given node has the property to work on.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return boolean
	 */
	public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		return ($node->hasProperty($this->propertyName));
	}

	/**
	 * Strips tags on the value of the property to work on.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$node->setProperty($this->propertyName, strip_tags($node->getProperty($this->propertyName)));
	}
}
?>