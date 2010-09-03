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
 * Tests for the ValueFactory implementation of TYPO3CR
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ValueFactoryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \F3\PHPCR\ValueFactory
	 */
	protected $valueFactory;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$this->valueFactory = new \F3\TYPO3CR\ValueFactory($this->mockObjectManager, $this->getMock('F3\PHPCR\SessionInterface'));
	}

	/**
	 * data provider for createValueGuessesCorrectType
	 */
	public function valuesAndExpectedTypes() {
		return array(
			array('This is a string', \F3\PHPCR\PropertyType::STRING),
			array(10, \F3\PHPCR\PropertyType::LONG),
			array(1.5, \F3\PHPCR\PropertyType::DOUBLE),
			array(FALSE, \F3\PHPCR\PropertyType::BOOLEAN),
			array(new \DateTime('2007-09-22'), \F3\PHPCR\PropertyType::DATE),
			array(new \F3\TYPO3CR\Binary(), \F3\PHPCR\PropertyType::BINARY),
		);
	}

	/**
	 * @dataProvider valuesAndExpectedTypes
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createValueGuessesCorrectType($value, $expectedType) {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\ValueInterface', $value, $expectedType)->will($this->returnValue(new \F3\TYPO3CR\Value($value, $expectedType)));
		$valueObject = $this->valueFactory->createValue($value);
		$this->assertEquals($valueObject->getType(), $expectedType);
	}

	/**
	 * data provider for createValueConvertsTypeIfRequested
	 */
	public function valuesAndTypesAndExpectedTypes() {
		return array(
			array('This is a string', TRUE, \F3\PHPCR\PropertyType::BOOLEAN),
			array('10 Euro', 10, \F3\PHPCR\PropertyType::LONG),
			array(15, 15.0, \F3\PHPCR\PropertyType::DOUBLE),
		);
	}

	/**
	 * Checks if type conversion works, if requested using createValue()
	 * @dataProvider valuesAndTypesAndExpectedTypes
	 * @test
	 */
	public function createValueConvertsTypeIfRequested($value, $expectedValue, $expectedType) {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\ValueInterface', $this->identicalTo($expectedValue), $expectedType)->will($this->returnValue(new \F3\TYPO3CR\Value($value, $expectedType)));
		$valueObject = $this->valueFactory->createValue($value, $expectedType);
		$this->assertSame($valueObject->getType(), $expectedType);
	}

	/**
	 * Checks if createValue can guess the REFERENCE type
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createValueFromNodeGuessesCorrectType() {
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNode->expects($this->any())->method('getIdentifier')->will($this->returnValue(\F3\FLOW3\Utility\Algorithms::generateUUID()));
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->any())->method('hasIdentifier')->will($this->returnValue(TRUE));
		$valueFactory = new \F3\TYPO3CR\ValueFactory($this->mockObjectManager, $mockSession);
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\ValueInterface', $mockNode->getIdentifier(), \F3\PHPCR\PropertyType::REFERENCE)->will($this->returnValue(new \F3\TYPO3CR\Value($mockNode->getIdentifier(), \F3\PHPCR\PropertyType::REFERENCE)));

		$value = $valueFactory->createValue($mockNode);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::REFERENCE, 'New Value object was not of type REFERENCE.');
		$this->assertEquals($value->getString(), $mockNode->getIdentifier(), 'The Value did not contain the Identifier of the passed Node object.');
	}

	/**
	 * Checks if createValue returns REFERENCE type for Node value if requested
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createValueFromNodeWithRequestedReferenceTypeWorks() {
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNode->expects($this->any())->method('getIdentifier')->will($this->returnValue(\F3\FLOW3\Utility\Algorithms::generateUUID()));
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->any())->method('hasIdentifier')->will($this->returnValue(TRUE));
		$valueFactory = new \F3\TYPO3CR\ValueFactory($this->mockObjectManager, $mockSession);
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\ValueInterface', $mockNode->getIdentifier(), \F3\PHPCR\PropertyType::REFERENCE)->will($this->returnValue(new \F3\TYPO3CR\Value($mockNode->getIdentifier(), \F3\PHPCR\PropertyType::REFERENCE)));

		$value = $valueFactory->createValue($mockNode, \F3\PHPCR\PropertyType::REFERENCE);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::REFERENCE, 'New Value object was not of type REFERENCE.');
		$this->assertEquals($value->getString(), $mockNode->getIdentifier(), 'The Value did not contain the Identifier of the passed Node object.');
	}

	/**
	 * Checks if createValue create a WEAKREFERENCE if $weak is TRUE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createValueFromNodeObservesWeakParameter() {
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNode->expects($this->any())->method('getIdentifier')->will($this->returnValue(\F3\FLOW3\Utility\Algorithms::generateUUID()));
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\ValueInterface', $mockNode->getIdentifier(), \F3\PHPCR\PropertyType::WEAKREFERENCE)->will($this->returnValue(new \F3\TYPO3CR\Value($mockNode->getIdentifier(), \F3\PHPCR\PropertyType::WEAKREFERENCE)));
		$value = $this->valueFactory->createValue($mockNode, NULL, TRUE);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::WEAKREFERENCE, 'New Value object was not of type WEAKREFERENCE.');
		$this->assertEquals($value->getString(), $mockNode->getIdentifier(), 'The Value did not contain the Identifier of the passed Node object.');
	}
}
?>