<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Domain::Service;

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
 * @subpackage Domain
 * @version $Id$
 */

/**
 * Testcase for the Time service
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TimeTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentDateTimeReturnsACurrentDateAndTime() {
		$almostCurrentTime = new DateTime();
		date_sub($almostCurrentTime, new DateInterval('P0DT1S'));

		$timeService = new F3::TYPO3::Domain::Service::Time();
		$currentTime = $timeService->getCurrentDateTime();
		$this->assertTrue($almostCurrentTime < $currentTime);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSimulatedDateTimeAllowsForMockingTheCurrentTime() {
		$simulatedCurrentTime = new DateTime();
		date_add($simulatedCurrentTime, new DateInterval('P1D'));

		$timeService = new F3::TYPO3::Domain::Service::Time();
		$timeService->setSimulatedDateTime($simulatedCurrentTime);

		$this->assertEquals($simulatedCurrentTime, $timeService->getCurrentDateTime());
	}
}


?>