<?php
namespace TYPO3\TYPO3\Tests\Unit\Routing;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Content Routepart Handler
 *
 */
class FrontendNodeRoutePartHandlerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function matchValueReturnsErrorValueIfNoSiteExistsForTheCurrentRequest() {
		$this->markTestIncomplete('Needs a new way to check, mock object not used because of new keyword!');

		$mockWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$mockContentContext = $this->getMock('TYPO3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($mockWorkspace));

		$routePartHandler = $this->getAccessibleMock('TYPO3\TYPO3\Routing\FrontendNodeRoutePartHandler', array('dummy'), array(), '', FALSE);
		$routePartHandler->_set('contentContext', $mockContentContext);

		$result = $routePartHandler->_call('matchValue', '');

		$this->assertEquals(\TYPO3\TYPO3\Routing\FrontendNodeRoutePartHandler::MATCHRESULT_NOSITE, $result);
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
	 */
	public function findValueToMatchReturnsTheGivenRequestPathUntilTheFirstDot($requestPath, $valueToMatch) {
		$routePartHandler = $this->getAccessibleMock('TYPO3\TYPO3\Routing\FrontendNodeRoutePartHandler', array('dummy'), array(), '', FALSE);
		$this->assertSame($valueToMatch, $routePartHandler->_call('findValueToMatch', $requestPath));
	}

	/**
	 * @test
	 */
	public function findValueToMatchRespectsSplitString() {
		$routePartHandler = $this->getAccessibleMock('TYPO3\TYPO3\Routing\FrontendNodeRoutePartHandler', array('dummy'), array(), '', FALSE);
		$routePartHandler->setSplitString('baz');

		$expectedResult = 'foo/bar/';
		$actualResult = $routePartHandler->_call('findValueToMatch', 'foo/bar/baz');
		$this->assertSame($expectedResult, $actualResult);
	}
}

?>