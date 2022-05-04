<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Infrastructure\Projection;

/*
 * This file is part of the Neos.ContentRepositoryMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventListener\Mapping\DefaultEventToListenerMappingProvider;
use Neos\EventSourcing\EventListener\Mapping\EventToListenerMapping;
use Neos\EventSourcing\EventPublisher\DeferEventPublisher;

/**
 * @internal
 */
final class RuntimeBlocker
{
    protected DefaultEventToListenerMappingProvider $eventToListenerMappingProvider;

    protected DeferEventPublisher $eventPublisher;

    protected ProcessedEventsAwareProjectorCollection $defaultProjectorsToBeBlocked;

    /**
     * @param iterable<int,ProcessedEventsAwareProjectorInterface> $defaultProjectorsToBeBlocked
     */
    public function __construct(
        DeferEventPublisher $eventPublisher,
        DefaultEventToListenerMappingProvider $eventToListenerMappingProvider,
        iterable $defaultProjectorsToBeBlocked
    ) {
        $this->eventPublisher = $eventPublisher;
        $this->eventToListenerMappingProvider = $eventToListenerMappingProvider;
        $this->defaultProjectorsToBeBlocked = new ProcessedEventsAwareProjectorCollection(
            $defaultProjectorsToBeBlocked
        );
    }

    public function blockUntilProjectionsAreUpToDate(
        CommandResult $commandResult,
        ProcessedEventsAwareProjectorCollection $projectorsToBeBlocked = null
    ): void {
        $this->eventPublisher->invoke();

        foreach ($projectorsToBeBlocked ?: $this->defaultProjectorsToBeBlocked as $projector) {
            $publishedEventsForProjector = $this->filterPublishedEventsByListener(
                $commandResult->getPublishedEvents(),
                get_class($projector)
            );
            $this->blockProjector($publishedEventsForProjector, $projector);
        }
    }

    private function filterPublishedEventsByListener(
        DomainEvents $publishedEvents,
        string $listenerClassName
    ): DomainEvents {
        $eventStoreId = $this->eventToListenerMappingProvider->getEventStoreIdentifierForListenerClassName(
            $listenerClassName
        );
        $listenerMappings = $this->eventToListenerMappingProvider->getMappingsForEventStore($eventStoreId)
            ->filter(static function (EventToListenerMapping $mapping) use ($listenerClassName) {
                return $mapping->getListenerClassName() === $listenerClassName;
            });
        $eventClassNames = [];
        foreach ($listenerMappings as $mapping) {
            $eventClassNames[$mapping->getEventClassName()] = true;
        }

        return $publishedEvents->filter(static function (DomainEventInterface $event) use ($eventClassNames) {
            if ($event instanceof DecoratedEvent) {
                $event = $event->getWrappedEvent();
            }
            return array_key_exists(get_class($event), $eventClassNames);
        });
    }

    private function blockProjector(DomainEvents $events, ProcessedEventsAwareProjectorInterface $projector): void
    {
        $attempts = 0;
        while (!$projector->hasProcessed($events)) {
            usleep(50000); // 50000μs = 50ms
            if (++$attempts > 300) { // 15 seconds
                $ids = '';
                foreach ($events as $event) {
                    if ($event instanceof DecoratedEvent) {
                        $ids .= '   ' . $event->getIdentifier();
                    }
                }

                throw new \RuntimeException(sprintf(
                    'TIMEOUT while waiting for event(s) %s for projector "%s" - check the error logs for details.',
                    $ids,
                    get_class($projector)
                ), 1550232279);
            }
        }
    }
}
