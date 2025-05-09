<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\EventStore\Model\Event\EventMetadata;

/**
 * @internal
 */
final readonly class InitiatingEventMetadata
{
    public const INITIATING_USER_ID = 'initiatingUserId';
    public const INITIATING_TIMESTAMP = 'initiatingTimestamp';

    private function __construct()
    {
    }

    /**
     * Add "initiatingUserId" and "initiatingTimestamp" metadata to all events.
     *                        This is done in order to keep information about the _original_ metadata when an
     *                        event is re-applied during publishing/rebasing
     * "initiatingUserId": The identifier of the user that originally triggered this event. This will never
     *                     be overridden if it is set once.
     * "initiatingTimestamp": The timestamp of the original event. The "recordedAt" timestamp will always be
     *                        re-created and reflects the time an event was actually persisted in a stream,
     *                        the "initiatingTimestamp" will be kept and is never overridden again.
     */
    public static function enrichEventsWithInitiatingMetadata(
        Events $events,
        UserId $initiatingUserId,
        \DateTimeImmutable $initiatingTimestamp,
    ): Events {
        $initiatingTimestampFormatted = $initiatingTimestamp->format(\DateTimeInterface::ATOM);

        return Events::fromArray(
            $events->map(function (EventInterface|DecoratedEvent $event) use (
                $initiatingUserId,
                $initiatingTimestampFormatted
            ) {
                $metadata = $event instanceof DecoratedEvent ? $event->eventMetadata?->value ?? [] : [];
                $metadata[self::INITIATING_USER_ID] ??= $initiatingUserId;
                $metadata[self::INITIATING_TIMESTAMP] ??= $initiatingTimestampFormatted;

                return DecoratedEvent::create($event, metadata: EventMetadata::fromArray($metadata));
            })
        );
    }

    public static function getInitiatingTimestamp(EventMetadata $eventMetadata): ?\DateTimeImmutable
    {
        $rawTimestamp = $eventMetadata->get(self::INITIATING_TIMESTAMP);
        if ($rawTimestamp === null) {
            return null;
        }
        return \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $rawTimestamp) ?: null;
    }

    public static function extractInitiatingMetadata(EventMetadata $eventMetadata): EventMetadata
    {
        return EventMetadata::fromArray(array_filter([
            self::INITIATING_USER_ID => $eventMetadata->get(self::INITIATING_USER_ID),
            self::INITIATING_TIMESTAMP => $eventMetadata->get(self::INITIATING_TIMESTAMP),
        ]));
    }
}
