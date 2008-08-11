<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3
 * @version $Id:$
 */

/**
 * Testcase for the domain model of a Page
 *
 * @package TYPO3
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_Domain_Model_PageTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author robert
	 */
	public function aPageCanBeHidden() {
		$page = new F3_TYPO3_Domain_Model_Page('Untitled');
		$page->hide();
		$this->assertTrue($page->isHidden());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function byDefaultAPageIsVisible() {
		$page = new F3_TYPO3_Domain_Model_Page('Untitled');
		$page->injectTimeService(new F3_TYPO3_Domain_Service_Time());
		$this->assertTrue($page->isVisible());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aPageIsInvisibleIfAStartTimeIsSetWhichLiesInTheFuture() {
		$timeService = new F3_TYPO3_Domain_Service_Time();
		$timeService->setSimulatedDateTime(new DateTime('2008-08-08T10:00+01:00'));

		$page = new F3_TYPO3_Domain_Model_Page('Untitled');
		$page->injectTimeService($timeService);

		$page->setStartTime(new DateTime('2008-08-08T18:00+01:00'));
		$this->assertFalse($page->isVisible());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aPageIsInvisibleIfAnEndTimeIsSetWhichLiesInThePast() {
		$timeService = new F3_TYPO3_Domain_Service_Time();
		$timeService->setSimulatedDateTime(new DateTime('2008-08-08T10:00+01:00'));

		$page = new F3_TYPO3_Domain_Model_Page('Untitled');
		$page->injectTimeService($timeService);

		$page->setEndTime(new DateTime('2008-08-07T12:00+01:00'));
		$this->assertFalse($page->isVisible());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aTitleMustNotExceed250Characters() {
		new F3_TYPO3_Domain_Model_Page(str_repeat('x', 255));
	}
}


?>