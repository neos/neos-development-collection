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
 * Testcase for the QOM QueryObjectModel
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Query_QOM_QueryObjectModelTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function queryObjectModelIsPrototype() {
		$selector = $this->getMock('F3_PHPCR_Query_QOM_SelectorInterface');
		$constraint = $this->getMock('F3_PHPCR_Query_QOM_ComparisonInterface');

		$this->assertNotSame(
			$this->componentFactory->getComponent('F3_TYPO3CR_Query_QOM_QueryObjectModel', $selector, $constraint, array(), array()),
			$this->componentFactory->getComponent('F3_TYPO3CR_Query_QOM_QueryObjectModel', $selector, $constraint, array(), array()),
			'Query_QOM_QueryObjectModel is not prototype.');
	}
}


?>