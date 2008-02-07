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

require_once('TYPO3CR_BaseTest.php');

/**
 * Test the NodeIterator implementation.
 *
 * @package		TYPO3CR
 * @subpackage	Tests
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @author		Ronny Unger <ru@php-workx.de>
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TYPO3CR_NodeIteratorTest extends TYPO3CR_BaseTest {

	/**
	 * @var T3_TYPO3CR_Node
	 */
	protected $rootNode;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$this->rootNode = $this->session->getRootNode();
	}

	/**
	 * Tests if getSize() returns the correct
	 * size.
	 *
	 * @author	Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getSizeWorks() {
		$iter = $this->rootNode->getNodes();
		$size = $this->rootNode->getNodes()->getSize();

		$count = 0;
		while ($iter->hasNext()) {
			$iter->nextNode();
			$count++;
		}
		$this->assertEquals($size, $count, "NodeIterator->getSize() does not return correct number.");
	}

	/**
	 * Tests if getPosition() return correct values.
	 *
	 * @author	Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getPositionWorks() {
		$iter = $this->rootNode->getNodes();
		$this->assertEquals(0, $iter->getPosition(), "Initial call to getPos() must return zero");
		$index = 0;
		while ($iter->hasNext()) {
			$iter->nextNode();
			$this->assertEquals(++$index, $iter->getPosition(), "Wrong position returned by getPos()");
		}
	}

	/**
	 * Tests if a T3_phpCR_NoSuchElementException} is thrown when nextNode()
	 * is called and there are no more nodes available.
	 *
	 * @author	Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function throwsNoSuchElementExceptionIfNoNodesAvailable() {
		$iter = $this->rootNode->getNodes();
		while ($iter->hasNext()) {
			$iter->nextNode();
		}

		try {
			$iter->nextNode();
			$this->fail("nextNode() must throw a NoSuchElementException when no nodes are available");
		} catch (T3_phpCR_NoSuchElementException $e) {
			// success
		}
	}

	/**
	 * Tests if skip() works correctly.
	 *
	 * @author	Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function skipWorks() {
		$iter = $this->rootNode->getNodes();
			// find out if there is anything we can skip
		$count = 0;
		while ($iter->hasNext()) {
			$iter->nextNode();
			$count++;
		}

		if ($count > 0) {
				// re-aquire iterator
			$iter = $this->rootNode->getNodes();
			$iter->rewind();
			$iter->skip($count);
			try {
				$iter->nextNode();
				$this->fail("nextNode() must throw a NoSuchElementException when no nodes are available");
			} catch (T3_phpCR_NoSuchElementException $e) {
				// success
			}

				// re-aquire iterator
			$iter = $this->rootNode->getNodes();
			try {
				$iter->skip($count + 1);
				$this->fail("skip() must throw a NoSuchElementException if one tries to skip past the end of the iterator");
			} catch (T3_phpCR_NoSuchElementException $e) {
				// success
			}
		}
	}
}
?>