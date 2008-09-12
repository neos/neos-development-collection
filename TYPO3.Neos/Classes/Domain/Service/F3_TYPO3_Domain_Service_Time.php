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
 * @version $Id$
 */

/**
 * A time service which allows for simulating dates, times and timezones.
 *
 * This service is used everywhere where the current time plays a role.
 * Because this time service is the central authority for telling the current
 * time, it is possible to simulate another point in time.
 *
 * @package TYPO3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Time {

	/**
	 * @var DateTime
	 */
	protected $simulatedDateTime;

	/**
	 * Returns the current date and time in form of a DateTime
	 * object.
	 *
	 * If you use this method for getting the current date and time
	 * everywhere in your code, it will be possible to simulate a certain
	 * time in unit tests or in the actual application.
	 *
	 * @return DateTime The current date and time - or a simulated version of it
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentDateTime() {
		return ($this->simulatedDateTime === NULL) ? new DateTime() : $this->simulatedDateTime;
	}

	/**
	 * Sets the simulated date and time. This time will then always be returned
	 * by getCurrentDateTime(). To undo this behaviour, just call this method
	 * again passing NULL.
	 *
	 * @param DateTime $simulatedDateTime A date and time to simulate. Pass NULL to deactivate the simulation.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSimulatedDateTime(DateTime $simulatedDateTime) {
		$this->simulatedDateTime = $simulatedDateTime;
	}
}
?>