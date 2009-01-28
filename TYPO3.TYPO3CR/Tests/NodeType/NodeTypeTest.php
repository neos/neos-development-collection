<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\NodeType;

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
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Tests for the NodeType implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NodeTypeTest extends \F3\Testing\BaseTestCase {

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function nodeTypeIsPrototype() {
		$firstInstance = $this->objectFactory->create('F3\TYPO3CR\NodeType\NodeType', 'name');
		$secondInstance = $this->objectFactory->create('F3\TYPO3CR\NodeType\NodeType', 'name');
		$this->assertNotSame($firstInstance, $secondInstance, 'F3\TYPO3CR\NodeType\NodeType is not prototype.');
	}

}
?>