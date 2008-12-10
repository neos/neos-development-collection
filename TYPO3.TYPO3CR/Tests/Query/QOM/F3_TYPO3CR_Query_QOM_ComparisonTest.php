<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Query\QOM;

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
 * Testcase for the QOM Comparison
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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