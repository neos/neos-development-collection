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
 * Tests for the AbstractItem implementation of TYPO3CR
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractItemTest extends \F3\Testing\BaseTestCase {

	/**
	 * Data provider for addNodeRejectsInvalidNames()
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function invalidLocalNames() {
		$nonSpace = array(
			array('/'),
			array(':'),
			array('['),
			array(']'),
			array('*'),
			array('|'),
			array(' '),
			array(chr(9)), // tab
			array(chr(10)), // line feed
			array(chr(13)) // carriage return
		);

		$oneChar = $nonSpace;
		$oneChar[] = array('');
		$oneChar[] = array('.');

		$twoChar = array();
		foreach ($oneChar as $character) {
			$twoChar[] = array($character[0] . $character[0]);
			$twoChar[] = array('.' . $character[0]);
			$twoChar[] = array($character[0] . '.');
		}

		$multiChar = array();
		foreach ($nonSpace as $character) {
			$multiChar[] = array($character[0] . $character[0] . $character[0]);
			$multiChar[] = array($character[0] . 'middle' . $character[0]);
		}

		return array_merge($oneChar, $twoChar, $multiChar);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @dataProvider invalidLocalNames
	 */
	public function isValidNameRejectsInvalidNames($name) {
		$item = $this->getMockForAbstractClass('F3\TYPO3CR\AbstractItem', array(), '', FALSE);
       	$this->assertFalse($item->isValidName($name));
	}

	/**
	 * Data provider for addNodeAcceptsValidNames(), tests some not too
	 * obvious valid names.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validLocalNames() {
		return array(
			array('. .'),
			array('...'),
			array('.a'),
			array('a.'),
			array('id')
		);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @dataProvider validLocalNames
	 */
	public function isValidNameAcceptsValidNames($name) {
		$item = $this->getMockForAbstractClass('F3\TYPO3CR\AbstractItem', array(), '', FALSE);
       	$this->assertTrue($item->isValidName($name));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getAncestorWorks() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getItem')->with('/News/Items');
		$item = $this->getAccessibleMockForAbstractClass('F3\TYPO3CR\AbstractItem', array(), '', FALSE);
		$item->_set('session', $mockSession);
		$item->expects($this->any())->method('getPath')->will($this->returnValue('/News/Items/Content'));
		$item->getAncestor(2);
	}

	/**
	 * Test if getting the ancestor of depth = n, where n is greater than depth
	 * of this node, throws an PHPCR_ItemNotFoundException for a sub node.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\ItemNotFoundException
	 */
	public function getAncestorOfGreaterDepthOnSubNodeThrowsException() {
		$item = $this->getMockForAbstractClass('F3\TYPO3CR\AbstractItem', array(), '', FALSE);
		$item->expects($this->once())->method('getPath')->will($this->returnValue('/Foo'));
		$item->getAncestor(2);
	}

	/**
	 * Test if getting the ancestor of negative depth throws an ItemNotFoundException.
	 *
	 * @test
	 * @expectedException \F3\PHPCR\ItemNotFoundException
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getAncestorOfNegativeDepthThrowsException() {
		$item = $this->getMockForAbstractClass('F3\TYPO3CR\AbstractItem', array(), '', FALSE);
		$item->getAncestor(-1);
	}

	/**
	 * Tests if isSame() returns FALSE when retrieving an item through different
	 * sessions
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function isSameReturnsTrueForSameNodes() {
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('default'));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->any())->method('getWorkspace')->will($this->returnValue($mockWorkspace));

		$node1 = $this->getMock('F3\PHPCR\NodeInterface');
		$node1->expects($this->any())->method('getSession')->will($this->returnValue($mockSession));
		$node1->expects($this->any())->method('getIdentifier')->will($this->returnValue('fakeUuid'));

		$node2 = $this->getMock('F3\TYPO3CR\Node', array('getSession', 'getIdentifier'), array(), '', FALSE);
		$node2->expects($this->any())->method('getSession')->will($this->returnValue($mockSession));
		$node2->expects($this->any())->method('getIdentifier')->will($this->returnValue('fakeUuid'));

		$this->assertTrue($node2->isSame($node1));
	}

	/**
	 * Tests if isSame() returns FALSE when retrieving an item through different
	 * sessions
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function isSameReturnsTrueForSameProperties() {
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('default'));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->any())->method('getWorkspace')->will($this->returnValue($mockWorkspace));

		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('isSame')->will($this->returnValue(TRUE));

		$property1 = $this->getMock('F3\PHPCR\PropertyInterface');
		$property1->expects($this->any())->method('getSession')->will($this->returnValue($mockSession));
		$property1->expects($this->any())->method('getName')->will($this->returnValue('testProperty'));
		$property1->expects($this->any())->method('getParent')->will($this->returnValue($node));

		$property2 = $this->getMock('F3\TYPO3CR\Property', array('getSession', 'getName', 'getParent'), array(), '', FALSE);
		$property2->expects($this->any())->method('getSession')->will($this->returnValue($mockSession));
		$property2->expects($this->any())->method('getName')->will($this->returnValue('testProperty'));
		$property2->expects($this->any())->method('getParent')->will($this->returnValue($node));

		$this->assertTrue($property2->isSame($property1));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDepthReturnsZeroForRootNode() {
		$item = $this->getMockForAbstractClass('F3\TYPO3CR\AbstractItem', array(), '', FALSE);
		$item->expects($this->once())->method('getPath')->will($this->returnValue('/'));
		$this->assertEquals(0, $item->getDepth());

	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDepthReturnsCorrectValue() {
		$item = $this->getMockForAbstractClass('F3\TYPO3CR\AbstractItem', array(), '', FALSE);
		$item->expects($this->once())->method('getPath')->will($this->returnValue('/Content/News/title'));
		$this->assertEquals(3, $item->getDepth());

	}

}
?>