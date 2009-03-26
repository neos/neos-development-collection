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
 * Testcase for the QueryResult
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class QueryResultTest extends \F3\Testing\BaseTestCase {

	public function setUp() {
		$this->mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodesReturnsANodeIterator() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$queryResult = new \F3\TYPO3CR\Query\QueryResult(array(), $mockSession);
		$queryResult->injectObjectFactory($this->mockObjectFactory);
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\PHPCR\NodeIteratorInterface')->will($this->returnValue(new \F3\TYPO3CR\NodeIterator));
		$this->assertType('F3\PHPCR\NodeIteratorInterface', $queryResult->getNodes(), 'QueryResult did not return a NodeIterator in getNodes().');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\PHPCR\RepositoryException
	 */
	public function getNodesThrowsRepositoryExceptionOnSecondCall() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$queryResult = new \F3\TYPO3CR\Query\QueryResult(array(), $mockSession);
		$queryResult->injectObjectFactory($this->mockObjectFactory);
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\PHPCR\NodeIteratorInterface')->will($this->returnValue(new \F3\TYPO3CR\NodeIterator));

		$queryResult->getNodes();
		$queryResult->getNodes();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\PHPCR\RepositoryException
	 */
	public function getNodesThrowsRepositoryExceptionIfCalledAfterGetRows() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$queryResult = new \F3\TYPO3CR\Query\QueryResult(array(), $mockSession);
		$queryResult->injectObjectFactory($this->mockObjectFactory);
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\PHPCR\Query\RowIteratorInterface')->will($this->returnValue(new \F3\TYPO3CR\Query\RowIterator));

		$queryResult->getRows();
		$queryResult->getNodes();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\PHPCR\RepositoryException
	 */
	public function getRowsThrowsRepositoryExceptionIfCalledAfterGetNodes() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$queryResult = new \F3\TYPO3CR\Query\QueryResult(array(), $mockSession);
		$queryResult->injectObjectFactory($this->mockObjectFactory);
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\PHPCR\NodeIteratorInterface');

		$queryResult->getNodes();
		$queryResult->getRows();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\PHPCR\RepositoryException
	 */
	public function getRowsThrowsRepositoryExceptionOnSecondCall() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$queryResult = new \F3\TYPO3CR\Query\QueryResult(array(), $mockSession);
		$queryResult->injectObjectFactory($this->mockObjectFactory);
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\PHPCR\Query\RowIteratorInterface')->will($this->returnValue(new \F3\TYPO3CR\Query\RowIterator));

		$queryResult->getRows();
		$queryResult->getRows();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\PHPCR\RepositoryException
	 */
	public function getNodesThrowsRepositoryExceptionOnMultipleSelectors() {
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$queryResult = new \F3\TYPO3CR\Query\QueryResult(array(array('a' => '', 'b' => '')), $mockSession);
		$queryResult->injectObjectFactory($this->mockObjectFactory);

		$queryResult->getNodes();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodesAsksForTheExpectedNodes() {
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->at(0))->method('getNodeByIdentifier')->with('12345')->will($this->returnValue($mockNode));
		$mockSession->expects($this->at(1))->method('getNodeByIdentifier')->with('67890')->will($this->returnValue($mockNode));
		$queryResult = new \F3\TYPO3CR\Query\QueryResult(array(array('a' => '12345'), array('a' => '67890')), $mockSession);
		$queryResult->injectObjectFactory($this->mockObjectFactory);
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\PHPCR\NodeIteratorInterface')->will($this->returnValue(new \F3\TYPO3CR\NodeIterator));

		$queryResult->getNodes();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRowsCreatesTheExpectedRows() {
		$mockRow = $this->getMock('F3\PHPCR\QueryRowInterface');
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$queryResult = new \F3\TYPO3CR\Query\QueryResult(array(array('a' => '12345'), array('a' => '67890')), $mockSession);
		$queryResult->injectObjectFactory($this->mockObjectFactory);
		$this->mockObjectFactory->expects($this->at(0))->method('create')->with('F3\PHPCR\Query\RowIteratorInterface')->will($this->returnValue(new \F3\TYPO3CR\Query\RowIterator));
		$this->mockObjectFactory->expects($this->at(1))->method('create')->with('F3\PHPCR\Query\RowInterface', array('a' => '12345'))->will($this->returnValue(new \F3\TYPO3CR\Query\Row(array(), $mockSession)));
		$this->mockObjectFactory->expects($this->at(2))->method('create')->with('F3\PHPCR\Query\RowInterface', array('a' => '67890'))->will($this->returnValue(new \F3\TYPO3CR\Query\Row(array(), $mockSession)));

		$queryResult->getRows();
	}

}

?>