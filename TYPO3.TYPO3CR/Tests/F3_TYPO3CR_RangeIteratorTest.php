<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR;

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
 * @version  $Id$
 */

/**
 * Test the RangeIterator implementation.
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version  $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class RangeIteratorTest extends \F3\Testing\BaseTestCase {

	/**
	 * Tests if getPosition() returns 0 initially.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPositionInitiallyReturnsZero() {
		$iterator = new \F3\TYPO3CR\RangeIterator();
		$this->assertEquals(0, $iterator->getPosition(), "getPosition() must initially return 0.");

		$array = array('one', 'two', 'three', 'four');
		$iterator = new \F3\TYPO3CR\RangeIterator($array);
		$this->assertEquals(0, $iterator->getPosition(), "getPosition() must initially return 0.");
	}

	/**
	 * Tests if getSize() returns the correct size.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getSizeReturnsCorrectResult() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new \F3\TYPO3CR\RangeIterator($array);

		$size = $iterator->getSize();
		$this->assertEquals(4, $size, "getSize() does not return correct number.");
	}

	/**
	 * Tests if getSize() returns the correct size.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getSizeReturnsCorrectResultAfterAppend() {
		$iterator = new \F3\TYPO3CR\RangeIterator();
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
		$iterator = new \F3\TYPO3CR\RangeIterator($array);

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
		$iterator = new \F3\TYPO3CR\RangeIterator($array);

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
		$iterator = new \F3\TYPO3CR\RangeIterator($array);

		$this->assertEquals(0, $iterator->getPosition(), "Initial call to getPosition() must return 0");
		$index = 0;
		while ($iterator->hasNext()) {
			$iterator->next();
			$this->assertEquals(++$index, $iterator->getPosition(), "Wrong position returned by getPosition()");
		}
	}

	/**
	 * Tests if a OutOfBoundsException} is thrown when nextNode()
	 * is called and there are no (more) nodes available.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \OutOfBoundsException
	 */
	public function throwsOutOfBoundsExceptionIfNoNodesAvailable() {
		$iterator = new \F3\TYPO3CR\RangeIterator();
		$iterator->next();
	}

	/**
	 * Tests if skip() works correctly.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \OutOfBoundsException
	 */
	public function skipToEndOfIteratorSetsPositionCorrectly() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new \F3\TYPO3CR\RangeIterator($array);

		$iterator->skip(4);
		$this->assertEquals(4, $iterator->getPosition(), "Call to getPosition() must return 4");
		$iterator->next();
	}

	/**
	 * Tests if skip() works correctly.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \OutOfBoundsException
	 */
	public function skipPastEndOfIteratorThrowsOutOfBoundsException() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new \F3\TYPO3CR\RangeIterator($array);

		$iterator->skip(5);
	}

	/**
	 * Tests if skip() works correctly.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function afterSkipTheExpectedItemIsReturned() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new \F3\TYPO3CR\RangeIterator($array);

		$iterator->next();
		$iterator->skip(2);
		$element = $iterator->next();
		$this->assertEquals('four', $element, 'Call to skip(2) must result in next element being "four", but we got "' . var_export($element, TRUE) . '"');
	}

	/**
	 * Tests if next() returns the correct element after remove().
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function afterRemoveTheExpectedItemIsReturned() {
		$array = array('one', 'two', 'three', 'four');
		$iterator = new \F3\TYPO3CR\RangeIterator($array);
		$iterator->next(); // returns "one"
		$iterator->remove(); // should remove "one", thus next() should give "two"
		$element = $iterator->next();
		$this->assertEquals('two', $element, "next() does not return correct result after remove().");

		$array = array('one', 'two', 'three', 'four');
		$iterator = new \F3\TYPO3CR\RangeIterator($array);
		$iterator->next(); // returns "one"
		$iterator->next(); // returns "two"
		$iterator->remove(); // should remove "two", thus next() should give "three"
		$element = $iterator->next();
		$this->assertEquals('three', $element, "next() does not return correct result after remove().");
	}

}
?>