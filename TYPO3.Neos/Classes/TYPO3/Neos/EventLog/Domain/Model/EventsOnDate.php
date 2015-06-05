<?php
namespace TYPO3\Neos\EventLog\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Helper Model which groups a number of events on a given day. Used especially in the view.
 */
class EventsOnDate {

	/**
	 * @var \DateTime
	 */
	protected $day;

	/**
	 * @var array<Event>
	 */
	protected $events = array();

	/**
	 * @param \DateTime $day
	 */
	public function __construct(\DateTime $day) {
		$this->day = $day;
	}

	/**
	 * add another event to this group
	 *
	 * @param Event $event
	 */
	public function add(Event $event) {
		$this->events[] = $event;
	}

	/**
	 * @return array
	 */
	public function getEvents() {
		return $this->events;
	}

	/**
	 * @return \DateTime
	 */
	public function getDay() {
		return $this->day;
	}
}