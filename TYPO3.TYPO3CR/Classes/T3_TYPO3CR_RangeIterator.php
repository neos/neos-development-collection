<?php
declare(encoding = 'utf-8');

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
 * A RangeIterator
 *
 * @package		TYPO3CR
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_TYPO3CR_RangeIterator implements T3_phpCR_RangeIteratorInterface {

	/**
	 * @var array
	 */
	protected $elements = array();

	/**
	 * @var integer
	 */
	protected $position = 0;

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
	 * Removes the last element returned by next(), i.e. the current element
	 * 
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function remove() {
		array_splice($this->elements, $this->getPosition(), 1);
	}

	/**
	 * Returns true if there are more elements available.
	 * 
	 * @return boolean
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	function hasNext() {
		return $this->getPosition() < $this->getSize();
	}

	/**
	 * Set the internal pointer to the next element and return it.
	 * 
	 * @return mixed The next element in the iteration
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function next() {
		if ($this->hasNext()) {
			return $this->elements[$this->position++];
		} else {
			throw new T3_phpCR_NoSuchElementException('Tried to go past the last element in the iterator.', 1187530869);
		}
	}

	/**
	 * Skip a number of elements in the iterator.
	 *
	 * @param integer $skipNum the non-negative number of elements to skip
	 * @return void
	 * @throws T3_phpCR_NoSuchElementException if skipped past the last element in the iterator.
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function skip($skipNum) {
		$newPosition = $this->getPosition() + $skipNum;
		if ($newPosition > $this->getSize()) {
			throw new T3_phpCR_NoSuchElementException('Skip operation past the last element in the iterator.', 1187530862);
		} else {
			$this->setPosition($newPosition);
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

	/**
	 * Sets the current position within the iterator.
	 * 
	 * @param integer $position The new position to set, 0-based
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setPosition($position) {
		$this->position = $position;
	}

	/**
	 * Returns the number of subsequent next() calls that can be
	 * successfully performed on this iterator.
	 * 
	 * This is the  number of items still available through this iterator. For
	 * example, for some node $n, $n->getNodes()->getSize() returns the number
	 * of child nodes of <code>N</code> visible through the current
	 * Session that have not yet been returned.
	 * 
	 * In some implementations precise information about the number of remaining
	 * elements may not be available. In such cases this method should return
	 * a reasonable upper bound on the number if such an estimate is available
	 * and -1 if it is not.
	 *
	 * @return integer
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function getNumberRemaining() {
		return ($this->getSize() - ($this->getPosition()+1));
	}




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
		$this->setPosition(0);
	}

	/**
	 * Returns the current element, i.e. the element the last next() call returned
	 * Required by SPL Iterator
	 * 
	 * @return mixed The current element
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function current() {
		return $this->elements[$this->getPosition()];
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