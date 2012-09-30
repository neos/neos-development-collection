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
 * Abstract transformation class, transformations should inherit from this.
 */
abstract class AbstractTransformation implements TransformationInterface {

	/**
	 * Returns TRUE, indicating that the given node can be transformed by this transformation.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return boolean
	 */
	function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		return TRUE;
	}

	/**
	 * Execute the transformation on the given node.
	 *
	 * This implementation returns the given node unchanged.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	function execute(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		return $node;
	}
}
?>