<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\ContentRepository\Core\EventStore\Events as DomainEvents;
use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Events;
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

    public function enrichAndNormalizeEvents(DomainEvents $events, CorrelationId $correlationId): Events
    {
        $initiatingUserId = $this->authProvider->getAuthenticatedUserId() ?? UserId::forSystemUser();
        $initiatingTimestamp = $this->clock->now();

        $eventsWithMetaData = InitiatingEventMetadata::enrichEventsWithInitiatingMetadata(
            $events,
            $initiatingUserId,
            $initiatingTimestamp
        );

        return Events::fromArray($eventsWithMetaData->map(function (EventInterface|DecoratedEvent $event) use ($correlationId) {
            $decoratedEvent = DecoratedEvent::create($event, correlationId: $correlationId);
            return $this->eventNormalizer->normalize($decoratedEvent);
        }));
    }
}
