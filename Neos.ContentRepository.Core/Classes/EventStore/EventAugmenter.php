<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Events as NormalizedEvents;
use Neos\EventStore\Model\EventsForCommit as NormalizedEventsForCommit;
use Neos\EventStore\Model\EventsForStream as NormalizedEventsForStream;
use Neos\EventStore\Model\EventsForStreams as NormalizedEventsForStreams;
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

    public function augmentAndNormalizeEventsToPublish(EventsToPublish $eventsToPublish, CorrelationId $correlationId): NormalizedEventsForCommit
    {
        $initiatingUserId = $this->authProvider->getAuthenticatedUserId() ?? UserId::forSystemUser();
        $initiatingTimestamp = $this->clock->now();

        $normalizedEventsForStreams = [];

        foreach ($eventsToPublish->eventsForStreams as $eventsForStream) {
            $normalizedEventsForStreams[] = NormalizedEventsForStream::create(
                streamName: $eventsForStream->streamName,
                events: $this->enrichAndNormalizeEvents($eventsForStream->events, $initiatingUserId, $initiatingTimestamp, $correlationId)
            );
        }

        return NormalizedEventsForCommit::create(
            NormalizedEventsForStreams::create(...$normalizedEventsForStreams),
            ExpectedStreamConstraints::create(...$eventsToPublish->expectedStreamConstraints)
        );
    }

    private function enrichAndNormalizeEvents(Events $events, UserId $initiatingUserId, \DateTimeImmutable $initiatingTimestamp, CorrelationId $correlationId): NormalizedEvents
    {
        $eventsWithMetaData = InitiatingEventMetadata::enrichEventsWithInitiatingMetadata(
            $events,
            $initiatingUserId,
            $initiatingTimestamp
        );

        return NormalizedEvents::fromArray($eventsWithMetaData->map(function (EventInterface|DecoratedEvent $event) use ($correlationId) {
            $decoratedEvent = DecoratedEvent::create($event, correlationId: $correlationId);
            return $this->eventNormalizer->normalize($decoratedEvent);
        }));
    }
}
