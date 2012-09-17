<?php
namespace TYPO3\TYPO3CR\Domain\Factory;

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
 * This factory creates proxy nodes based on "real" nodes. Its main purpose is
 * to assure that proxy nodes created for a certain node are unique in memory.
 *
 * This implementation is a preliminary solution and certainly needs to be refactored
 * into a generic NodeFactory and a streamlined treat-with-context mechanism which
 * fits better into the DDD approach of Factories.
 *
 * @FLOW3\Scope("singleton")
 */
class ProxyNodeFactory {

	/**
	 * @var \SplObjectStorage
	 */
	protected $proxyNodes;

	/**
	 * Constructs this factory.
	 *
	 */
	public function __construct() {
		$this->proxyNodes = new \SplObjectStorage;
	}

	/**
	 * Creates a proxy node from the given Node.
	 *
	 * If this factory has previously created a ProxyNode for the given Node, it will
	 * return the same ProxyNode again.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return \TYPO3\TYPO3CR\Domain\Model\ProxyNode
	 */
	public function createFromNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		if ($this->proxyNodes->contains($node) === FALSE) {
			$this->proxyNodes[$node] = new \TYPO3\TYPO3CR\Domain\Model\ProxyNode($node);
		}
		return $this->proxyNodes[$node];
	}
}

?>