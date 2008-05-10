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
 * Test the NodeIterator implementation.
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version  $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_NodeIteratorTest extends F3_Testing_BaseTestCase {

	/**
	 * Tests if getSize() returns the correct size.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getSizeReturnsCorrectResult() {
		$iterator = new F3_TYPO3CR_NodeIterator();
		$iterator->append('one');
		$iterator->append('two');
		$iterator->append('three');
		$iterator->append('four');

		$size = $iterator->getSize();
		$this->assertEquals(4, $size, "getSize() does not return correct number.");
	}

	/**
	 * Tests if hasNext() and nextNode() see all elements
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasNextAndNextNodeIterateThroughAllElements() {
		$iterator = new F3_TYPO3CR_NodeIterator();
		$iterator->append('one');
		$iterator->append('two');
		$iterator->append('three');
		$iterator->append('four');

		$count = 0;
		while ($iterator->hasNext()) {
			$iterator->nextNode();
			$count++;
		}
		$this->assertEquals(4, $count, "hasNext() and nextNode() do not iterate over all elements.");
	}

	/**
	 * Tests if getPosition() return correct values.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPositionWorks() {
		$iterator = new F3_TYPO3CR_NodeIterator();
		$iterator->append('one');
		$iterator->append('two');
		$iterator->append('three');
		$iterator->append('four');

		$this->assertEquals(0, $iterator->getPosition(), "Initial call to getPosition() must return 0");
		$index = 0;
		while ($iterator->hasNext()) {
			$iterator->nextNode();
			$this->assertEquals(++$index, $iterator->getPosition(), "Wrong position returned by getPosition()");
		}
	}

	/**
	 * Tests if a F3_phpCR_NoSuchElementException} is thrown when nextNode()
	 * is called and there are no (more) nodes available.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function throwsNoSuchElementExceptionIfNoNodesAvailable() {
		$iterator = new F3_TYPO3CR_NodeIterator();
		try {
			$iterator->nextNode();
			$this->fail("nextNode() must throw a NoSuchElementException when no nodes are available");
		} catch (F3_phpCR_NoSuchElementException $e) {
			// success
		}
	}

	/**
	 * Tests if skip() works correctly.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function skipToEndOfIteratorSetsPositionCorrectly() {
		$iterator = new F3_TYPO3CR_NodeIterator();
		$iterator->append('one');
		$iterator->append('two');
		$iterator->append('three');
		$iterator->append('four');

		$iterator->skip(4);
		$this->assertEquals(4, $iterator->getPosition(), "Call to getPosition() must return 4");
		try {
			$iterator->nextNode();
			$this->fail("nextNode() after skip() to the end must throw a NoSuchElementException");
		} catch (F3_phpCR_NoSuchElementException $e) {
			// success
		}
	}

	/**
	 * Tests if skip() works correctly.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function skipPastEndOfIteratorThrowsNoSuchElementException() {
		$iterator = new F3_TYPO3CR_NodeIterator();
		$iterator->append('one');
		$iterator->append('two');
		$iterator->append('three');
		$iterator->append('four');

		try {
			$iterator->skip(5);
			$this->fail("skip() must throw a NoSuchElementException if one tries to skip past the end of the iterator");
		} catch (F3_phpCR_NoSuchElementException $e) {
			// success
		}
	}
}
?>