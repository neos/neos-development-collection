<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Routing;

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
 * Testcase for the Page Routepart Handler
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageRoutePartHandlerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchValueCreatesAContentContextAndResolvesTheNode() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE);

		$mockPage = $this->getMock('F3\TYPO3\Domain\Model\Content\Page', array(), array(), '', FALSE);
		$mockPage->FLOW3_Persistence_Entity_UUID = '4D666FAE-0414-718D-050D6DAEAB872A38';

		$mockNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode', array('getContent'), array(), '', FALSE);
		$mockNode->expects($this->once())->method('getContent')->with($mockContentContext)->will($this->returnValue($mockPage));

		$mockNodeService = $this->getMock('F3\TYPO3\Domain\Service\NodeService', array('getNode'), array(), '', FALSE);
		$mockNodeService->expects($this->once())->method('getNode')->with($mockSite, '/foo/bar/baz')->will($this->returnValue($mockNode));

		$mockContentContext->expects($this->any())->method('getNodeService')->will($this->returnValue($mockNodeService));
		$mockContentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($mockSite));
		$mockContentContext->expects($this->once())->method('setNodePath')->with('/foo/bar/baz');

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->with('F3\TYPO3\Domain\Service\ContentContext')->will($this->returnValue($mockContentContext));

		$routePartHandler = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Routing\PageRoutePartHandler'), array('dummy'), array(), '', FALSE);
		$routePartHandler->_set('objectFactory', $mockObjectFactory);

		$result = $routePartHandler->_call('matchValue', 'foo/bar/baz');

		$this->assertTrue($result);
		$this->assertSame(array('__identity' => '4D666FAE-0414-718D-050D6DAEAB872A38'), $routePartHandler->_get('value'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchValueReturnsFALSEIfNoNodeWithTheGivenPathExists() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE);

		$mockNodeService = $this->getMock('F3\TYPO3\Domain\Service\NodeService', array('getNode'), array(), '', FALSE);
		$mockNodeService->expects($this->once())->method('getNode')->with($mockSite, '/foo/bar/baz')->will($this->returnValue(NULL));

		$mockContentContext->expects($this->any())->method('getNodeService')->will($this->returnValue($mockNodeService));
		$mockContentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->with('F3\TYPO3\Domain\Service\ContentContext')->will($this->returnValue($mockContentContext));

		$routePartHandler = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Routing\PageRoutePartHandler'), array('dummy'), array(), '', FALSE);
		$routePartHandler->_set('objectFactory', $mockObjectFactory);

		$result = $routePartHandler->_call('matchValue', 'foo/bar/baz');

		$this->assertFalse($result);
	}
}

?>