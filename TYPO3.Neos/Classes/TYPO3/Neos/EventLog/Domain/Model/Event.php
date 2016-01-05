<?php
namespace TYPO3\Neos\EventLog\Domain\Model;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Base class for generic events
 *
 * @Flow\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\Table(
 *    indexes={
 *        @ORM\Index(name="eventtype",columns={"eventtype"})
 *    }
 * )
 */
class Event
{
    /**
     * When was this event?
     *
     * @var \DateTime
     */
    protected $timestamp;

    /**
     * We introduce an auto_increment column to be able to sort events at the same timestamp
     *
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(nullable=true, options={"unsigned"=true})
     */
    protected $uid;

    /**
     * What was this event about? Is a required string constant.
     *
     * @var string
     */
    protected $eventType;

    /**
     * The identifier of the account that triggered this event. Optional.
     *
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $accountIdentifier;

    /**
     * Payload of the event.
     *
     * @ORM\Column(type="flow_json_array")
     * @var array
     */
    protected $data = array();

    /**
     * The parent event, if exists. E.g. if a "move node" operation triggered a bunch of other events, or a "publish"
     *
     * @var Event
     * @ORM\ManyToOne(inversedBy="childEvents")
     */
    protected $parentEvent;

    /**
     * Child events, of this event
     *
     * @var ArrayCollection<TYPO3\Neos\EventLog\Domain\Model\Event>
     * @ORM\OneToMany(targetEntity="TYPO3\Neos\EventLog\Domain\Model\Event", mappedBy="parentEvent", cascade="persist")
     */
    protected $childEvents;

    /**
     * Create a new event
     *
     * @param string $eventType
     * @param array $data
     * @param string $user
     * @param Event $parentEvent
     */
    public function __construct($eventType, $data, $user = null, Event $parentEvent = null)
    {
        $this->timestamp = new \DateTime();
        $this->eventType = $eventType;
        $this->data = $data;
        $this->accountIdentifier = $user;
        $this->parentEvent = $parentEvent;

        $this->childEvents = new ArrayCollection();

        if ($this->parentEvent !== null) {
            $parentEvent->addChildEvent($this);
        }
    }

    /**
     * Return the type of this event
     *
     * @return string
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * Return the timestamp of this event
     *
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Return the payload of this event
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return the identifier of the account (if any) which triggered this event
     *
     * @return string
     */
    public function getAccountIdentifier()
    {
        return $this->accountIdentifier;
    }

    /**
     * Return the parent event (if any)
     *
     * @return Event
     */
    public function getParentEvent()
    {
        return $this->parentEvent;
    }

    /**
     * Return the child events (if any)
     *
     * @return array
     */
    public function getChildEvents()
    {
        return $this->childEvents;
    }

    /**
     * Add a new child event. Is called from the child event's constructor.
     *
     * @param Event $childEvent
     * @return void
     */
    public function addChildEvent(Event $childEvent)
    {
        $this->childEvents->add($childEvent);
    }
}
