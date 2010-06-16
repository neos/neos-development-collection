<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Node Service
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class NodeServiceTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function contentServiceIsBoundToASpecificContentContext() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);

		$nodeService = $this->getAccessibleMock('F3\TYPO3\Domain\Service\NodeService', array('dummy'), array($mockContentContext));
		$this->assertSame($mockContentContext, $nodeService->_get('contentContext'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodeFindsANodeOnTheFirstLevelOfASite() {
		$otherNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$otherNode->expects($this->once())->method('getNodeName')->will($this->returnValue('bar'));

		$expectedNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$expectedNode->expects($this->once())->method('getNodeName')->will($this->returnValue('foo'));

		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site');
		$mockSite->expects($this->once())->method('hasChildNodes')->will($this->returnValue(TRUE));
		$mockSite->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($otherNode, $expectedNode)));

		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->once())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$nodeService = new \F3\TYPO3\Domain\Service\NodeService($mockContentContext);

		$actualNode = $nodeService->getNode('/foo');
		$this->assertSame($expectedNode, $actualNode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodeFindsANodeOnTheSecondLevelOfASite() {
		$node2a = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$node2a->expects($this->once())->method('getNodeName')->will($this->returnValue('2a'));

		$expectedNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$expectedNode->expects($this->once())->method('getNodeName')->will($this->returnValue('2b'));

		$node1a = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$node1a->expects($this->once())->method('getNodeName')->will($this->returnValue('1a'));
		$node1a->expects($this->never())->method('hasChildNodes');

		$node1b = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$node1b->expects($this->once())->method('getNodeName')->will($this->returnValue('1b'));
		$node1b->expects($this->once())->method('hasChildNodes')->will($this->returnValue(TRUE));
		$node1b->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($node2a, $expectedNode)));

		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site');
		$mockSite->expects($this->once())->method('hasChildNodes')->will($this->returnValue(TRUE));
		$mockSite->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($node1a, $node1b)));

		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->once())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$nodeService = new \F3\TYPO3\Domain\Service\NodeService($mockContentContext);

		$actualNode = $nodeService->getNode('/1b/2b');
		$this->assertSame($expectedNode, $actualNode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodeReturnsNullIfAPathWithMoreThanOneSegmentDoesNotLeadToANode() {
		$nodeBaz = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$nodeBaz->expects($this->once())->method('getNodeName')->will($this->returnValue('baz'));

		$nodeFoo = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$nodeFoo->expects($this->once())->method('getNodeName')->will($this->returnValue('foo'));
		$nodeFoo->expects($this->once())->method('hasChildNodes')->will($this->returnValue(TRUE));
		$nodeFoo->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($nodeBaz)));

		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site');
		$mockSite->expects($this->once())->method('hasChildNodes')->will($this->returnValue(TRUE));
		$mockSite->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($nodeFoo)));

		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->once())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$nodeService = new \F3\TYPO3\Domain\Service\NodeService($mockContentContext);

		$this->assertNull($nodeService->getNode('/foo/doesntexist'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodeReturnsFalseIfObjectStructureHasNotEnoughChildNodesForTheGivenNumberOfPathSegments() {
		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site');
		$mockSite->expects($this->once())->method('hasChildNodes')->will($this->returnValue(FALSE));

		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->once())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$nodeService = new \F3\TYPO3\Domain\Service\NodeService($mockContentContext);

		$this->assertNull($nodeService->getNode('/foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodesOnPathReturnsAnArrayOfAllNodesLyingOnTheGivenPath() {
		$node2a = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$node2a->expects($this->once())->method('getNodeName')->will($this->returnValue('2a'));

		$node2b = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$node2b->expects($this->once())->method('getNodeName')->will($this->returnValue('2b'));

		$node1a = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$node1a->expects($this->once())->method('getNodeName')->will($this->returnValue('1a'));
		$node1a->expects($this->never())->method('hasChildNodes');

		$node1b = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$node1b->expects($this->once())->method('getNodeName')->will($this->returnValue('1b'));
		$node1b->expects($this->once())->method('hasChildNodes')->will($this->returnValue(TRUE));
		$node1b->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($node2a, $node2b)));

		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site');
		$mockSite->expects($this->once())->method('hasChildNodes')->will($this->returnValue(TRUE));
		$mockSite->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($node1a, $node1b)));

		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->once())->method('getCurrentSite')->will($this->returnValue($mockSite));

    	$nodeService = new \F3\TYPO3\Domain\Service\NodeService($mockContentContext);

		$expectedNodes = array($node1b, $node2b);

		$actualNodes = $nodeService->getNodesOnPath('/1b/2b');
		$this->assertSame($expectedNodes, $actualNodes);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodesOnPathReturnsTheIndexNodeIfTheReferenceNodeSupportsIt() {
		$node1a = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');

		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site');
		$mockSite->expects($this->once())->method('getIndexNode')->will($this->returnValue($node1a));

		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->once())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$nodeService = new \F3\TYPO3\Domain\Service\NodeService($mockContentContext);

		$expectedNodes = array($node1a);
		$actualNodes = $nodeService->getNodesOnPath('/');
		$this->assertSame($expectedNodes, $actualNodes);
	}
}

?>