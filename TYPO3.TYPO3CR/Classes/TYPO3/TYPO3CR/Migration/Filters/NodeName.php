<?php
namespace TYPO3\TYPO3CR\Migration\Filters;

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
 * Filter nodes by node name.
 */
class NodeName implements FilterInterface {

	/**
	 * The node name to match on.
	 *
	 * @var string
	 */
	protected $nodeName;

	/**
	 * Sets the node type name to match on.
	 *
	 * @param string $nodeName
	 * @return void
	 */
	public function setNodeName($nodeName) {
		$this->nodeName = $nodeName;
	}

	/**
	 * Returns TRUE if the given node is of the node type this filter expects.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return boolean
	 */
	public function matches(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		return $node->getName() === $this->nodeName;
	}

}
?>