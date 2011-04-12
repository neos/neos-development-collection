<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Domain\Factory;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * This factory creates proxy nodes based on "real" nodes. Its main purpose is
 * to assure that proxy nodes created for a certain node are unique in memory.
 *
 * This implementation is a preliminary solution and certainly needs to be refactored
 * into a generic NodeFactory and a streamlined treat-with-context mechanism which
 * fits better into the DDD approach of Factories.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class ProxyNodeFactory {

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \SplObjectStorage
	 */
	protected $proxyNodes;

	/**
	 * Constructs this factory.
	 *
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @return \F3\TYPO3CR\Domain\Model\ProxyNode
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createFromNode(\F3\TYPO3CR\Domain\Model\NodeInterface $node) {
		if ($this->proxyNodes->contains($node) === FALSE) {
			$this->proxyNodes[$node] = $this->objectManager->create('F3\TYPO3CR\Domain\Model\ProxyNode', $node);
		}
		return $this->proxyNodes[$node];
	}
}

?>