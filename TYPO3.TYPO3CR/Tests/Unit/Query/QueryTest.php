<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Query;

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
 * Testcase for the Query
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	 * @expectedException \InvalidArgumentException
	 */
	public function bindValueThrowsExceptionOnUnknownVariableName() {
		$query = new \F3\TYPO3CR\Query\Query();
		$query->bindValue('someVariable', $this->getMock('F3\PHPCR\ValueInterface'));
	}

}


?>