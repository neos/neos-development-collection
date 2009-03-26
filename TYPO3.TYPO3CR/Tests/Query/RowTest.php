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
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the Row implementation
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RowTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\PHPCR\RepositoryException
	 */
	public function getNodeThrowsRepositoryExceptionOnMultipleSelectors() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');

		$row = new \F3\TYPO3CR\Query\Row(array('a' => '12345', 'b' => '67890'), $mockSession);
		$row->getNode();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\PHPCR\RepositoryException
	 */
	public function getNodeThrowsRepositoryExceptionOnUnknownSelector() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');

		$row = new \F3\TYPO3CR\Query\Row(array('a' => '12345'), $mockSession);
		$row->getNode('c');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodeAsksForTheExpectedNode() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with('12345');

		$row = new \F3\TYPO3CR\Query\Row(array('a' => '12345'), $mockSession);
		$row->getNode();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodeAsksForTheExpectedNodeIfSelectorNameIsGiven() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with('12345');

		$row = new \F3\TYPO3CR\Query\Row(array('a' => '12345'), $mockSession);
		$row->getNode('a');
	}

	/**
	 * "If this Row is from a result involving outer joins, it may have no Node
	 * corresponding to the specified selector. In such a case this method
	 * returns null."
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodeReturnsNullIfNoNodeForSelectorNameIsPresent() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');

		$row = new \F3\TYPO3CR\Query\Row(array('a' => NULL), $mockSession);
		$this->assertNull($row->getNode('a'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPathAsksNodeForPath() {
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNode->expects($this->once())->method('getPath');
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with('12345')->will($this->returnValue($mockNode));

		$row = new \F3\TYPO3CR\Query\Row(array('a' => '12345'), $mockSession);
		$row->getPath();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPathAsksTheExpectedNodeForPathIfSelectorNameIsGiven() {
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNode->expects($this->once())->method('getPath');
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with('12345')->will($this->returnValue($mockNode));

		$row = new \F3\TYPO3CR\Query\Row(array('a' => '12345'), $mockSession);
		$row->getPath('a');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\PHPCR\RepositoryException
	 */
	public function getPathThrowsRepositoryExceptionOnMultipleSelectors() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');

		$row = new \F3\TYPO3CR\Query\Row(array('a' => '12345', 'b' => '67890'), $mockSession);
		$row->getPath();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\PHPCR\RepositoryException
	 */
	public function getPathThrowsRepositoryExceptionOnUnknownSelector() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');

		$row = new \F3\TYPO3CR\Query\Row(array('a' => '12345'), $mockSession);
		$row->getPath('c');
	}

	/**
	 * "If this Row is from a result involving outer joins, it may have no Node
	 * corresponding to the specified selector. In such a case this method
	 * returns null."
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPathReturnsNullIfNoNodeForSelectorNameIsPresent() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');

		$row = new \F3\TYPO3CR\Query\Row(array('a' => NULL), $mockSession);
		$this->assertNull($row->getPath('a'));
	}

}

?>