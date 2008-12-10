<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Query;

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
class QueryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException InvalidArgumentException
	 */
	public function setLimitAcceptsOnlyIntegers() {
		$query = new \F3\TYPO3CR\Query\Query();
		$query->setLimit(1.5);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException InvalidArgumentException
	 */
	public function setLimitRejectsIntegersLessThanOne() {
		$query = new \F3\TYPO3CR\Query\Query();
		$query->setLimit(0);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException InvalidArgumentException
	 */
	public function setOffsetAcceptsOnlyIntegers() {
		$query = new \F3\TYPO3CR\Query\Query();
		$query->setOffset(1.5);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException InvalidArgumentException
	 */
	public function setOffsetRejectsIntegersLessThanZero() {
		$query = new \F3\TYPO3CR\Query\Query();
		$query->setOffset(-1);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function executeReturnsQueryResult() {
		$this->markTestIncomplete('Not yet implemented');
	}

}


?>