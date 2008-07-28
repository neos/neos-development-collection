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
 * @version $Id$
 */

/**
 * Testcase for the Query
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Query_QueryTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function queryIsPrototype() {
		$this->assertNotSame(
			$this->componentFactory->getComponent('F3_TYPO3CR_Query_Query'),
			$this->componentFactory->getComponent('F3_TYPO3CR_Query_Query'),
			'Query_Query is not prototype.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setLimitAcceptsOnlyIntegers() {
		$query = new F3_TYPO3CR_Query_Query();
		try {
			$query->setLimit(1.5);
			$this->fail('setLimit() did not throw an exception when given a non-integer argument.');
		} catch (InvalidArgumentException $e) {}
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setLimitRejectsIntegersLessThanOne() {
		$query = new F3_TYPO3CR_Query_Query();
		try {
			$query->setLimit(0);
			$this->fail('setLimit() did not throw an exception when given an argument less than 1.');
		} catch (InvalidArgumentException $e) {}
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setOffsetAcceptsOnlyIntegers() {
		$query = new F3_TYPO3CR_Query_Query();
		try {
			$query->setOffset(1.5);
			$this->fail('setOffset() did not throw an exception when given a non-integer argument.');
		} catch (InvalidArgumentException $e) {}
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setOffsetRejectsIntegersLessThanZero() {
		$query = new F3_TYPO3CR_Query_Query();
		try {
			$query->setOffset(-1);
			$this->fail('setOffset() did not throw an exception when given an argument less than 0.');
		} catch (InvalidArgumentException $e) {}
	}
}


?>