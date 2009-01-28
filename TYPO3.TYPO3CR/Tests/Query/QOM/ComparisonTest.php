<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Query\QOM;

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
 * @version $Id$
 */

/**
 * Testcase for the QOM Comparison
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ComparisonTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function comparisonIsPrototype() {
		$comparison1 = $this->objectFactory->create('F3\TYPO3CR\Query\QOM\Comparison', $this->getMock('F3\PHPCR\Query\QOM\DynamicOperandInterface'), 1, $this->getMock('F3\PHPCR\Query\QOM\StaticOperandInterface'));
		$comparison2 = $this->objectFactory->create('F3\TYPO3CR\Query\QOM\Comparison', $this->getMock('F3\PHPCR\Query\QOM\DynamicOperandInterface'), 1, $this->getMock('F3\PHPCR\Query\QOM\StaticOperandInterface'));
		$this->assertNotSame($comparison1, $comparison2, 'Query_QOM_Comparison is not prototype.');
	}
}


?>