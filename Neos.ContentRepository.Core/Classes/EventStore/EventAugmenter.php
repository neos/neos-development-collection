<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Events as NormalisedEvents;
use Neos\EventStore\Model\EventsForCommit as NormalisedEventsForCommit;
use Neos\EventStore\Model\EventsForStream as NormalisedEventsForStream;
use Neos\EventStore\Model\EventsForStreams as NormalisedEventsForStreams;
use Neos\EventStore\Model\EventStream\ExpectedStreamConstraints;
use Psr\Clock\ClockInterface;

/**
 * @internal prepares events for commit
 */
final readonly class EventAugmenter
{
    public function __construct(
        private EventNormalizer $eventNormalizer,
        private ClockInterface $clock,
        private AuthProviderInterface $authProvider,
    ) {
    }

    /**
     * @param class-string<CommandInterface> $commandClassName
     */
    public function correlationIdForCommandClass(string $commandClassName): CorrelationId
    {
        return CorrelationId::fromString(sprintf('%s_%s', substr($commandClassName, strrpos($commandClassName, '\\') + 1, 20), bin2hex(random_bytes(9))));
    }

    public function augmentAndNormalizeEventsToPublish(EventsToPublish $eventsToPublish, CorrelationId $correlationId): NormalisedEventsForCommit
    {
        $initiatingUserId = $this->authProvider->getAuthenticatedUserId() ?? UserId::forSystemUser();
        $initiatingTimestamp = $this->clock->now();

        $normalisedEventsForStreams = [];

        foreach ($eventsToPublish->eventsForStreams as $eventsForStream) {
            $normalisedEventsForStreams[] = NormalisedEventsForStream::create(
                streamName: $eventsForStream->streamName,
                events: $this->enrichAndNormalizeEvents($eventsForStream->events, $initiatingUserId, $initiatingTimestamp, $correlationId)
            );
        }

        return NormalisedEventsForCommit::create(
            NormalisedEventsForStreams::create(...$normalisedEventsForStreams),
            ExpectedStreamConstraints::create(...$eventsToPublish->expectedStreamConstraints)
        );
    }

    private function enrichAndNormalizeEvents(Events $events, UserId $initiatingUserId, \DateTimeImmutable $initiatingTimestamp, CorrelationId $correlationId): NormalisedEvents
    {
        $eventsWithMetaData = InitiatingEventMetadata::enrichEventsWithInitiatingMetadata(
            $events,
            $initiatingUserId,
            $initiatingTimestamp
        );

        return NormalisedEvents::fromArray($eventsWithMetaData->map(function (EventInterface|DecoratedEvent $event) use ($correlationId) {
            $decoratedEvent = DecoratedEvent::create($event, correlationId: $correlationId);
            return $this->eventNormalizer->normalize($decoratedEvent);
        }));
    }
}
