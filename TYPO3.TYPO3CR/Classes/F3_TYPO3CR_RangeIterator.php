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
 * @version $Id$
 */

/**
 * A RangeIterator
 *
 * @package TYPO3CR
 * @version  $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 */
class RangeIterator implements \F3\PHPCR\RangeIteratorInterface {

	/**
	 * @var array
	 */
	protected $elements;

	/**
	 * @var integer
	 */
	protected $position = 0;

	/**
	 * Constructs a new RangeIterator
	 *
	 * @param array $elements The elements to populate the iterator with
	 * @return void
	 */
	public function __construct(array $elements = array()) {
		$this->elements = $elements;
	}

	/**
	 * Append a new element to the end of the iteration
	 *
	 * @param mixed $element The element to append to the iteration
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function append($element) {
		$this->elements[] = $element;
	}

	/**
	 * Removes the last element returned by next()
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function remove() {
		$positionToRemove = $this->getPosition()-1;
		array_splice($this->elements, $positionToRemove, 1);
			// array_splice resets the array pointer, so we fix it together with the internal position
		for ($skipped = 0; $skipped < --$this->position; $skipped++) next($this->elements);
	}

	/**
	 * Returns FALSE if there are more elements available.
	 *
	 * @return boolean
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasNext() {
		return $this->getPosition() < $this->getSize();
	}

	/**
	 * Return the next (i.e. current) element in the iterator
	 *
	 * @return mixed The next element in the iteration
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function next() {
		if ($this->hasNext()) {
			$this->position++;
			$element = current($this->elements);
			next($this->elements);
			return $element;
		} else {
			throw new \OutOfBoundsException('Tried to go past the last element in the iterator.', 1187530869);
		}
	}

	/**
	 * Skip a number of elements in the iterator.
	 *
	 * @param integer $skipNum the non-negative number of elements to skip
	 * @return void
	 * @throws OutOfBoundsException if skipped past the last element in the iterator.
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function skip($skipNum) {
		$newPosition = $this->getPosition() + $skipNum;
		if ($newPosition > $this->getSize()) {
			throw new \OutOfBoundsException('Skip operation past the last element in the iterator.', 1187530862);
		} else {
			$this->position = $newPosition;
			for ($skipped = 0; $skipped < $skipNum; $skipped++) next($this->elements);
		}
	}

	/**
	 * Returns the total number of of items available through this iterator.
	 *
	 * For example, for some node $n, $n->getNodes()->getSize() returns the number
	 * of child nodes of $n visible through the current Session.
	 *
	 * In some implementations precise information about the number of elements may
	 * not be available. In such cases this method must return -1. API clients will
	 * then be able to use RangeIterator->getNumberRemaining() to get an
	 * estimate on the number of elements.
	 *
	 * @return integer
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function getSize() {
		return count($this->elements);
	}

	/**
	 * Returns the current position within the iterator. The number
	 * returned is the 0-based index of the next element in the iterator,
	 * i.e. the one that will be returned on the subsequent next() call.
	 *
	 * Note that this method does not check if there is a next element,
	 * i.e. an empty iterator will always return 0.
	 *
	 * @return integer The current position, 0-based
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function getPosition() {
		return $this->position;
	}

	// non-JSR-283 methods below

	/**
	 * Alias for hasNext(), valid() is required by SPL Iterator
	 *
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function valid() {
		return $this->hasNext();
	}

	/**
	 * Rewinds the element cursor, required by SPL Iterator
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function rewind() {
		$this->position = 0;
		reset($this->elements);
	}

	/**
	 * Returns the current element, i.e. the element the last next() call returned
	 * Required by SPL Iterator
	 *
	 * @return mixed The current element
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function current() {
		return current($this->elements);
	}

	/**
	 * Returns the key of the current element
	 * Required by SPL Iterator
	 *
	 * return integer The key of the current element
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function key() {
		return $this->getPosition();
	}
}
?>