<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Tests\Unit\Routing;

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
 * Testcase for the Content Routepart Handler
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class NodeRoutePartHandlerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchValueReturnsErrorValueIfNoSiteExistsForTheCurrentRequest() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getWorkspace')->will($this->returnValue('WORKSPACE'));
		$mockContentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue(NULL));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\TYPO3\Domain\Service\ContentContext')->will($this->returnValue($mockContentContext));

		$routePartHandler = $this->getAccessibleMock('F3\TYPO3\Routing\NodeRoutePartHandler', array('dummy'), array(), '', FALSE);
		$routePartHandler->_set('objectManager', $mockObjectManager);

		$result = $routePartHandler->_call('matchValue', '');

		$this->assertEquals(\F3\TYPO3\Routing\NodeRoutePartHandler::MATCHRESULT_NOSITE, $result);
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
		$routePartHandler = $this->getAccessibleMock('F3\TYPO3\Routing\NodeRoutePartHandler', array('dummy'), array(), '', FALSE);
		$this->assertSame($valueToMatch, $routePartHandler->_call('findValueToMatch', $requestPath));
	}

}

?>