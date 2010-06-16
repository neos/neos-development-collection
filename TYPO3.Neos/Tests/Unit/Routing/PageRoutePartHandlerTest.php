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
	public function getContentContextReturnsTheCurrentContentContext() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\TYPO3\Domain\Service\ContentContext')->will($this->returnValue($mockContentContext));

		$routePartHandler = $this->getAccessibleMock('F3\TYPO3\Routing\PageRoutePartHandler', array('dummy'), array(), '', FALSE);
		$routePartHandler->_set('objectManager', $mockObjectManager);

		$this->assertSame($mockContentContext, $routePartHandler->getContentContext());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchValueResolvesTheSpecifiedNode() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE);

		$mockPage = $this->getMock('F3\TYPO3\Domain\Model\Content\Page', array(), array(), '', FALSE);
		$mockPage->FLOW3_Persistence_Entity_UUID = '4D666FAE-0414-718D-050D6DAEAB872A38';

		$mockNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode', array('getContent'), array(), '', FALSE);
		$mockNode->expects($this->once())->method('getContent')->with($mockContentContext)->will($this->returnValue($mockPage));

		$mockNodeService = $this->getMock('F3\TYPO3\Domain\Service\NodeService', array('getNode'), array(), '', FALSE);
		$mockNodeService->expects($this->once())->method('getNode')->with('/foo/bar/baz')->will($this->returnValue($mockNode));

		$mockContentContext->expects($this->any())->method('getNodeService')->will($this->returnValue($mockNodeService));
		$mockContentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($mockSite));
		$mockContentContext->expects($this->once())->method('setCurrentNodePath')->with('/foo/bar/baz');
		$mockContentContext->expects($this->once())->method('setCurrentPage')->with($mockPage);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\TYPO3\Domain\Service\ContentContext')->will($this->returnValue($mockContentContext));

		$routePartHandler = $this->getAccessibleMock('F3\TYPO3\Routing\PageRoutePartHandler', array('dummy'), array(), '', FALSE);
		$routePartHandler->_set('objectManager', $mockObjectManager);

		$result = $routePartHandler->_call('matchValue', 'foo/bar/baz');

		$this->assertTrue($result);
		$this->assertSame(array('__identity' => '4D666FAE-0414-718D-050D6DAEAB872A38'), $routePartHandler->_get('value'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchValueReturnsErrorValueIfNoSiteExistsForTheCurrentRequest() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue(NULL));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\TYPO3\Domain\Service\ContentContext')->will($this->returnValue($mockContentContext));

		$routePartHandler = $this->getAccessibleMock('F3\TYPO3\Routing\PageRoutePartHandler', array('dummy'), array(), '', FALSE);
		$routePartHandler->_set('objectManager', $mockObjectManager);

		$result = $routePartHandler->_call('matchValue', '');

		$this->assertEquals(\F3\TYPO3\Routing\PageRoutePartHandler::MATCHRESULT_NOSITE, $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchValueReturnsErrorValueIfNoNodeExistsForTheGivenRequestPath() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE);

		$mockNodeService = $this->getMock('F3\TYPO3\Domain\Service\NodeService', array('getNode'), array(), '', FALSE);
		$mockNodeService->expects($this->once())->method('getNode')->with('/')->will($this->returnValue(NULL));

		$mockContentContext->expects($this->any())->method('getNodeService')->will($this->returnValue($mockNodeService));
		$mockContentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\TYPO3\Domain\Service\ContentContext')->will($this->returnValue($mockContentContext));

		$routePartHandler = $this->getAccessibleMock('F3\TYPO3\Routing\PageRoutePartHandler', array('dummy'), array(), '', FALSE);
		$routePartHandler->_set('objectManager', $mockObjectManager);

		$result = $routePartHandler->_call('matchValue', '');

		$this->assertEquals(\F3\TYPO3\Routing\PageRoutePartHandler::MATCHRESULT_NOSUCHNODE, $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchValueReturnsErrorValueIfNodeSpecifiedByMatchDoesNotExist() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE);

    	$mockNodeService = $this->getMock('F3\TYPO3\Domain\Service\NodeService', array('getNode'), array(), '', FALSE);
		$mockNodeService->expects($this->once())->method('getNode')->with('/')->will($this->returnValue(NULL));

		$mockContentContext->expects($this->any())->method('getNodeService')->will($this->returnValue($mockNodeService));
		$mockContentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\TYPO3\Domain\Service\ContentContext')->will($this->returnValue($mockContentContext));

		$routePartHandler = $this->getAccessibleMock('F3\TYPO3\Routing\PageRoutePartHandler', array('dummy'), array(), '', FALSE);
		$routePartHandler->_set('objectManager', $mockObjectManager);

		$result = $routePartHandler->_call('matchValue', '');

		$this->assertEquals(\F3\TYPO3\Routing\PageRoutePartHandler::MATCHRESULT_NOSUCHNODE, $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchValueReturnsErrorValueIfPageSpecifiedByMatchDoesNotExist() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE);

		$mockNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode', array(), array(), '', FALSE);
		$mockNode->expects($this->once())->method('getContent')->will($this->returnValue(new \stdClass));

    	$mockNodeService = $this->getMock('F3\TYPO3\Domain\Service\NodeService', array('getNode'), array(), '', FALSE);
		$mockNodeService->expects($this->once())->method('getNode')->with('/foo/bar')->will($this->returnValue($mockNode));

		$mockContentContext->expects($this->any())->method('getNodeService')->will($this->returnValue($mockNodeService));
		$mockContentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\TYPO3\Domain\Service\ContentContext')->will($this->returnValue($mockContentContext));

		$routePartHandler = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Routing\PageRoutePartHandler'), array('dummy'), array(), '', FALSE);
		$routePartHandler->_set('objectManager', $mockObjectManager);

		$result = $routePartHandler->_call('matchValue', 'foo/bar');

		$this->assertEquals(\F3\TYPO3\Routing\PageRoutePartHandler::MATCHRESULT_NOSUCHPAGE, $result);
	}

	/**
	 * Data provider for ... see below
	 */
	public function requestPaths() {
		return array(
			array('homepage', 'homepage'),
			array('homepage.html', 'homepage'),
			array('homepage/subpage.html', 'homepage/subpage'),
			array('homepage/subpage.rss.xml', 'homepage/subpage')
		);
	}

	/**
	 * @test
	 * @dataProvider requestPaths
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findValueToMatchReturnsTheGivenRequestPathUntilTheFirstDot($requestPath, $valueToMatch) {
		$routePartHandler = $this->getAccessibleMock('F3\TYPO3\Routing\PageRoutePartHandler', array('dummy'), array(), '', FALSE);
		$this->assertSame($valueToMatch, $routePartHandler->_call('findValueToMatch', $requestPath));
	}

}

?>