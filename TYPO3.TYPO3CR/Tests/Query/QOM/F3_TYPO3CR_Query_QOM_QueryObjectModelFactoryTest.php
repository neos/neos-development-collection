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
 * Testcase for the QOM factory
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Query_QOM_QueryObjectModelFactoryTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_PHPCR_Query_QOM_QueryObjectModelFactoryInterface
	 */
	protected $QOMFactory;

	public function setUp() {
		$this->QOMFactory = new F3_TYPO3CR_Query_QOM_QueryObjectModelFactory();
		$this->QOMFactory->injectComponentFactory($this->componentFactory);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function selectorReturnsSelector() {
		$this->assertType('F3_PHPCR_Query_QOM_SelectorInterface', $this->QOMFactory->selector('nt:base'), 'The QOM factory did not return a Selector as expected.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function comparisonReturnsComparison() {
		$operand1 = $this->getMock('F3_PHPCR_Query_QOM_DynamicOperandInterface');
		$operand2 = $this->getMock('F3_PHPCR_Query_QOM_StaticOperandInterface');
		$this->assertType('F3_PHPCR_Query_QOM_ComparisonInterface', $this->QOMFactory->comparison($operand1, F3_PHPCR_Query_QOM_QueryObjectModelConstantsInterface::OPERATOR_EQUAL_TO, $operand2), 'The QOM factory did not return a Comparison as expected.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function propertyValueReturnsPropertyValue() {
		$this->assertType('F3_PHPCR_Query_QOM_PropertyValueInterface', $this->QOMFactory->propertyValue('someProp'), 'The QOM factory did not return a PropertyValue as expected.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function bindVariableReturnsBindVariableValue() {
		$this->assertType('F3_PHPCR_Query_QOM_BindVariableValueInterface', $this->QOMFactory->bindVariable('someName'), 'The QOM factory did not return a BindVariableValue as expected.');
	}

}


?>