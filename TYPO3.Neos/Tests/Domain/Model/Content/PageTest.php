<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Content;

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
 * @package TYPO3
 * @version $Id$
 */

/**
 * Testcase for the domain model of a Page
 *
 * @package TYPO3
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageTest extends \F3\Testing\BaseTestCase {

	/**
	 * test
	 * @author robert
	 */
	public function aPageCanBeHidden() {
		$page = new \F3\TYPO3\Domain\Model\Content\Page('Untitled');
		$page->hide();
		$this->assertTrue($page->isHidden());
	}

	/**
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function byDefaultAPageIsVisible() {
		$pageClassName = $this->buildAccessibleProxy('F3\TYPO3\Domain\Model\Content\Page');
		$page = new $pageClassName('Untitled');
		$page->_set('timeService', new \F3\TYPO3\Domain\Service\TimeService());
		$this->assertTrue($page->isVisible());
	}

	/**
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aPageIsInvisibleIfAStartTimeIsSetWhichLiesInTheFuture() {
		$timeService = new \F3\TYPO3\Domain\Service\TimeService();
		$timeService->setSimulatedDateTime(new \DateTime('2008-08-08T10:00+01:00'));

		$pageClassName = $this->buildAccessibleProxy('F3\TYPO3\Domain\Model\Content\Page');
		$page = new $pageClassName('Untitled');
		$page->_set('timeService', $timeService);

		$page->setStartTime(new \DateTime('2008-08-08T18:00+01:00'));
		$this->assertFalse($page->isVisible());
	}

	/**
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aPageIsInvisibleIfAnEndTimeIsSetWhichLiesInThePast() {
		$timeService = new \F3\TYPO3\Domain\Service\TimeService();
		$timeService->setSimulatedDateTime(new \DateTime('2008-08-08T10:00+01:00'));

		$pageClassName = $this->buildAccessibleProxy('F3\TYPO3\Domain\Model\Content\Page');
		$page = new $pageClassName('Untitled');
		$page->_set('timeService', $timeService);

		$page->setEndTime(new \DateTime('2008-08-07T12:00+01:00'));
		$this->assertFalse($page->isVisible());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theTitleIsUsedAsTheLabel() {
		$page = $this->getMock('F3\TYPO3\Domain\Model\Content\Page', array('dummy'), array(), '', FALSE);
		$page->setTitle('El título');
		$this->assertSame('El título', $page->getLabel());
	}
}


?>