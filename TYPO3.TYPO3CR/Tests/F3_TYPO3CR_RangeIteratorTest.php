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
 * Test the RangeIterator implementation.
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version  $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_RangeIteratorTest extends F3_Testing_BaseTestCase {

	/**
	 * Tests if getPosition() returns 0 initially.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPositionInitiallyReturnsZero() {
		$iterator = new F3_TYPO3CR_RangeIterator();
		$this->assertEquals(0, $iterator->getPosition(), "getPosition() must initially return 0.");

		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);
		$this->assertEquals(0, $iterator->getPosition(), "getPosition() must initially return 0.");
	}

	/**
	 * Tests if getSize() returns the correct size.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getSizeReturnsCorrectResult() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);

		$size = $iterator->getSize();
		$this->assertEquals(4, $size, "getSize() does not return correct number.");
	}

	/**
	 * Tests if getSize() returns the correct size.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getSizeReturnsCorrectResultAfterAppend() {
		$iterator = new F3_TYPO3CR_RangeIterator();
		$iterator->append('one');
		$iterator->append('two');
		$iterator->append('three');
		$iterator->append('four');

		$size = $iterator->getSize();
		$this->assertEquals(4, $size, "getSize() does not return correct number.");
	}

	/**
	 * Tests if getSize() returns the correct size after remove().
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getSizeReturnsCorrectResultAfterRemove() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);

		$iterator->remove();
		$size = $iterator->getSize();
		$this->assertEquals(3, $size, "getSize() does not return correct number.");
	}

	/**
	 * Tests if hasNext() and nextNode() see all elements
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasNextAndNextNodeIterateThroughAllElements() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);

		$count = 0;
		while ($iterator->hasNext()) {
			$iterator->next();
			$count++;
		}
		$this->assertEquals(4, $count, 'hasNext() and nextNode() do not iterate over all elements, saw ' . $count . ' elements, expected 4.');
	}

	/**
	 * Tests if getPosition() return correct values.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPositionWorks() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);

		$this->assertEquals(0, $iterator->getPosition(), "Initial call to getPosition() must return 0");
		$index = 0;
		while ($iterator->hasNext()) {
			$iterator->next();
			$this->assertEquals(++$index, $iterator->getPosition(), "Wrong position returned by getPosition()");
		}
	}

	/**
	 * Tests if a F3_PHPCR_NoSuchElementException} is thrown when nextNode()
	 * is called and there are no (more) nodes available.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function throwsNoSuchElementExceptionIfNoNodesAvailable() {
		$iterator = new F3_TYPO3CR_RangeIterator();
		try {
			$iterator->next();
			$this->fail("nextNode() must throw a NoSuchElementException when no nodes are available");
		} catch (F3_PHPCR_NoSuchElementException $e) {
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
		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);

		$iterator->skip(4);
		$this->assertEquals(4, $iterator->getPosition(), "Call to getPosition() must return 4");
		try {
			$iterator->next();
			$this->fail("nextNode() after skip() to the end must throw a NoSuchElementException");
		} catch (F3_PHPCR_NoSuchElementException $e) {
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
		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);

		try {
			$iterator->skip(5);
			$this->fail("skip() must throw a NoSuchElementException if one tries to skip past the end of the iterator");
		} catch (F3_PHPCR_NoSuchElementException $e) {
			// success
		}
	}

	/**
	 * Tests if skip() works correctly.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function afterSkipTheExpectedItemIsReturned() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);

		$iterator->next();
		$iterator->skip(2);
		$element = $iterator->next();
		$this->assertEquals('four', $element, 'Call to skip(2) must result in next element being "four", but we got "' . var_export($element, TRUE) . '"');
	}

	/**
	 * Tests if getNumberRemaining() works correctly.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNumberRemainingTellsTheTruth() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);

		$this->assertEquals(4, $iterator->getNumberRemaining(), 'Call to getNumberRemaining() must return 4.');
		$iterator->next();
		$this->assertEquals(3, $iterator->getNumberRemaining(), 'Call to getNumberRemaining() must return 3.');
		$iterator->next();
		$this->assertEquals(2, $iterator->getNumberRemaining(), 'Call to getNumberRemaining() must return 2.');
		$iterator->next();
		$this->assertEquals(1, $iterator->getNumberRemaining(), 'Call to getNumberRemaining() must return 1.');
		$iterator->next();
		$this->assertEquals(0, $iterator->getNumberRemaining(), 'Call to getNumberRemaining() must return 0.');

		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);

		$this->assertEquals(4, $iterator->getNumberRemaining(), 'Call to getNumberRemaining() must return 4.');
		$iterator->next();
		$iterator->remove();
		$this->assertEquals(3, $iterator->getNumberRemaining(), 'Call to getNumberRemaining() must return 3.');
	}

	/**
	 * Tests if next() returns the correct element after remove().
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function afterRemoveTheExpectedItemIsReturned() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);
		$iterator->next(); // returns "one"
		$iterator->remove(); // should remove "one", thus next() should give "two"
		$element = $iterator->next();
		$this->assertEquals('two', $element, "next() does not return correct result after remove().");

		$array = array('one', 'two', 'three', 'four');
		$iterator = new F3_TYPO3CR_RangeIterator($array);
		$iterator->next(); // returns "one"
		$iterator->next(); // returns "two"
		$iterator->remove(); // should remove "two", thus next() should give "three"
		$element = $iterator->next();
		$this->assertEquals('three', $element, "next() does not return correct result after remove().");
	}

}
?>