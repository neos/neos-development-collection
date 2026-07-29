<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * @internal
 */
final readonly class RebaseableEvent
{
    public function __construct(
        public PublishableToWorkspaceInterface $event,
        public Event $originalEvent,
        public EventMetadata $initiatingMetaData,
        public SequenceNumber $originalSequenceNumber,
        public RebaseableEvents $causedEvents,
    ) {
    }

    public function withCausedEvent(RebaseableEvent $causedEvent): self
    {
        if ($causedEvent->originalEvent->causationId === null || $causedEvent->originalEvent->causationId->value !== $this->originalEvent->causationId->value) {
            throw new \RuntimeException(sprintf('Expected the causation id of event %d (%s) to match %d (%s)', $causedEvent->originalSequenceNumber->value, $causedEvent->originalEvent->causationId->value, $this->originalSequenceNumber->value, $this->originalEvent->causationId->value), 1785308199);
        }

        return new self(
            event: $this->event,
            originalEvent: $this->originalEvent,
            initiatingMetaData: $this->initiatingMetaData,
            originalSequenceNumber: $this->originalSequenceNumber,
            causedEvents: $this->causedEvents->withAppended($causedEvent),
        );
    }
}
