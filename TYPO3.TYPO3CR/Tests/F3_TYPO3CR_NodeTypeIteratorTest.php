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
class F3_TYPO3CR_NodeTypeIteratorTest extends F3_Testing_BaseTestCase {

	public function setUp() {
		$this->mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccessInterface');
		$this->mockStorageAccess->expects($this->any())->method('getRawNodeTypeById')->will($this->returnValue(array('name' => 'SuperDuperNodeType')));

		$this->iterator = new F3_TYPO3CR_NodeTypeIterator();
	}

	/**
	 * Tests if getSize() returns the correct size.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getSizeReturnsCorrectResult() {
		$this->iterator->append(new F3_TYPO3CR_NodeType(1, $this->mockStorageAccess, $this->componentManager));
		$this->iterator->append(new F3_TYPO3CR_NodeType(1, $this->mockStorageAccess, $this->componentManager));
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
		$this->iterator->append(new F3_TYPO3CR_NodeType(1, $this->mockStorageAccess, $this->componentManager));
		$this->iterator->append(new F3_TYPO3CR_NodeType(1, $this->mockStorageAccess, $this->componentManager));
		$count = 0;
		while ($this->iterator->hasNext()) {
			$this->iterator->nextNodeType();
			$count++;
		}
		$this->assertEquals(2, $count, "hasNext() and nextNode() do not iterate over all elements.");
	}

	/**
	 * Tests if a F3_phpCR_NoSuchElementException is thrown when nextNodeType()
	 * is called and there are no (more) nodetypes available.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function throwsNoSuchElementExceptionIfNoNodesAvailable() {
		try {
			$this->iterator->nextNodeType();
			$this->fail("nextNodeType() must throw a NoSuchElementException when no nodetypes are available");
		} catch (F3_phpCR_NoSuchElementException $e) {
			// success
		}
	}
}
?>