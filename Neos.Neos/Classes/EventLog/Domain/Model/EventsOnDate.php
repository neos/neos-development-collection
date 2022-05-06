<?php
namespace Neos\Neos\EventLog\Domain\Model;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Helper Model which groups a number of events on a given day. Used especially in the view.
 */
class EventsOnDate
{
    /**
     * @var \DateTime
     */
    protected $day;

    /**
     * @var array<Event>
     */
    protected $events = [];

    /**
     * @param \DateTime $day
     */
    public function __construct(\DateTime $day)
    {
        $this->day = $day;
    }

    /**
     * add another event to this group
     */
    public function add(Event $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return array<Event>
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return \DateTime
     */
    public function getDay()
    {
        return $this->day;
    }
}
