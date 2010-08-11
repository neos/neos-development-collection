<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Controller\Frontend;

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
 * Testcase for the Page controller
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageControllerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function showActionAssignsPageToDataValueForExtDirectFormat() {
		$mockPage = $this->getMock('F3\TYPO3\Domain\Model\Content\Page', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockView = $this->getMock('F3\FLOW3\MVC\View\ViewInterface');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Controller\Service\PageController'), array('dummy'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('view', $mockView);

		$mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('extdirect'));

		$expectedValue = array(
			'data' => $mockPage,
			'success' => true
		);

		$mockView->expects($this->atLeastOnce())->method('assign')->with('value', $expectedValue);

		$controller->showAction($mockPage);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function updateActionCallsUpdateOnPageRepository() {
		$mockPage = $this->getMock('F3\TYPO3\Domain\Model\Content\Page', array(), array(), '', FALSE);
		$mockPageRepository = $this->getMock('F3\TYPO3\Domain\Repository\Content\PageRepository', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockView = $this->getMock('F3\FLOW3\MVC\View\ViewInterface');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Controller\Service\PageController'), array('redirect'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->injectPageRepository($mockPageRepository);
		$controller->_set('view', $mockView);

		$mockPageRepository->expects($this->atLeastOnce())->method('update')->with($mockPage);

		$controller->updateAction($mockPage);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function updateActionSetsSuccessValueForExtDirectFormat() {
		$mockPage = $this->getMock('F3\TYPO3\Domain\Model\Content\Page', array(), array(), '', FALSE);
		$mockPageRepository = $this->getMock('F3\TYPO3\Domain\Repository\Content\PageRepository', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockView = $this->getMock('F3\FLOW3\MVC\View\ViewInterface');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Controller\Service\PageController'), array('redirect'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->injectPageRepository($mockPageRepository);
		$controller->_set('view', $mockView);

		$mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('extdirect'));

		$expectedValue = array(
			'success' => true
		);

		$mockView->expects($this->atLeastOnce())->method('assign')->with('value', $expectedValue);

		$controller->updateAction($mockPage);
	}
}
?>