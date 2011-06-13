<?php
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
class FrontendNodeRoutePartHandlerTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchValueReturnsErrorValueIfNoSiteExistsForTheCurrentRequest() {
		$this->markTestIncomplete('Needs a new way to check, mock object not used because of new keyword!');

		$mockWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($mockWorkspace));

		$routePartHandler = $this->getAccessibleMock('F3\TYPO3\Routing\FrontendNodeRoutePartHandler', array('dummy'), array(), '', FALSE);
		$routePartHandler->_set('contentContext', $mockContentContext);

		$result = $routePartHandler->_call('matchValue', '');

		$this->assertEquals(\F3\TYPO3\Routing\FrontendNodeRoutePartHandler::MATCHRESULT_NOSITE, $result);
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
		$routePartHandler = $this->getAccessibleMock('F3\TYPO3\Routing\FrontendNodeRoutePartHandler', array('dummy'), array(), '', FALSE);
		$this->assertSame($valueToMatch, $routePartHandler->_call('findValueToMatch', $requestPath));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function findValueToMatchRespectsSplitString() {
		$routePartHandler = $this->getAccessibleMock('F3\TYPO3\Routing\FrontendNodeRoutePartHandler', array('dummy'), array(), '', FALSE);
		$routePartHandler->setSplitString('baz');

		$expectedResult = 'foo/bar/';
		$actualResult = $routePartHandler->_call('findValueToMatch', 'foo/bar/baz');
		$this->assertSame($expectedResult, $actualResult);
	}
}

?>