<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3CR
 * @subpackage Tests
 * @version  $Id$
 */

/**
 * Test the NodeTypeIterator implementation.
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version  $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_NodeType_NodeTypeIteratorTest extends F3_Testing_BaseTestCase {

	public function setUp() {
		$this->mockStorageBackend = $this->getMock('F3_TYPO3CR_Storage_BackendInterface');
		$this->mockStorageBackend->expects($this->any())->method('getRawNodeType')->will($this->returnValue(array('name' => 'SuperDuperNodeType')));

		$this->iterator = new F3_TYPO3CR_NodeType_NodeTypeIterator();
	}

	/**
	 * Tests if getSize() returns the correct size.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getSizeReturnsCorrectResult() {
		$this->iterator->append(new F3_TYPO3CR_NodeType_NodeType('SuperDuperNodeType'));
		$this->iterator->append(new F3_TYPO3CR_NodeType_NodeType('SuperDuperNodeType'));
		$size = $this->iterator->getSize();
		$this->assertEquals(2, $size, "getSize() does not return correct number.");
	}

	/**
	 * Tests if hasNext() and nextNode() see all elements
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasNextAndNextNodeIterateThroughAllElements() {
		$this->iterator->append(new F3_TYPO3CR_NodeType_NodeType('SuperDuperNodeType'));
		$this->iterator->append(new F3_TYPO3CR_NodeType_NodeType('SuperDuperNodeType'));
		$count = 0;
		while ($this->iterator->hasNext()) {
			$this->iterator->nextNodeType();
			$count++;
		}
		$this->assertEquals(2, $count, "hasNext() and nextNode() do not iterate over all elements.");
	}

	/**
	 * Tests if a OutOfBoundsException is thrown when nextNodeType()
	 * is called and there are no (more) nodetypes available.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function throwsOutOfBoundsExceptionIfNoNodesAvailable() {
		try {
			$this->iterator->nextNodeType();
			$this->fail("nextNodeType() must throw a OutOfBoundsException when no nodetypes are available");
		} catch (OutOfBoundsException $e) {
			// success
		}
	}
}
?>