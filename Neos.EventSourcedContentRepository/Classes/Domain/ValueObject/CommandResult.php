<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\GraphProjector;
use Neos\EventSourcedContentRepository\Domain\Projection\ContentStream\ContentStreamProjector;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceProjector;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\ProcessedEventsAwareProjectorInterface;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventListener\Mapping\DefaultEventToListenerMappingProvider;
use Neos\EventSourcing\EventListener\Mapping\EventToListenerMapping;
use Neos\EventSourcing\EventPublisher\DeferEventPublisher;
use Neos\Flow\Annotations as Flow;

/**
 */
final class CommandResult
{
    /**
     * @var DomainEvents
     */
    protected $publishedEvents;

    /**
     * @Flow\Inject(lazy=false)
     * @var GraphProjector
     */
    protected $graphProjector;

    /**
     * @Flow\Inject(lazy=false)
     * @var WorkspaceProjector
     */
    protected $workspaceProjector;

    /**
     * @Flow\Inject(lazy=false)
     * @var ContentStreamProjector
     */
    protected $contentStreamProjector;

    /**
     * @Flow\Inject
     * @var DefaultEventToListenerMappingProvider
     */
    protected $eventToListenerMappingProvider;

    /**
     * @Flow\Inject
     * @var DeferEventPublisher
     */
    protected $eventPublisher;

    protected function __construct(DomainEvents $publishedEvents)
    {
        $this->publishedEvents = $publishedEvents;
    }


    public static function fromPublishedEvents(DomainEvents $events): self
    {
        return new static($events);
    }

    public static function createEmpty(): self
    {
        return new static(DomainEvents::createEmpty());
    }

    public function merge(CommandResult $commandResult): self
    {
        return self::fromPublishedEvents($this->publishedEvents->appendEvents($commandResult->publishedEvents));
    }

    public function blockUntilProjectionsAreUpToDate(): void
    {
        $this->eventPublisher->invoke();

        $publishedEventsForGraphProjector = $this->filterPublishedEventsByListener(GraphProjector::class);
        self::blockProjector($publishedEventsForGraphProjector, $this->graphProjector);

        $publishedEventsForWorkspaceProjector = $this->filterPublishedEventsByListener(WorkspaceProjector::class);
        self::blockProjector($publishedEventsForWorkspaceProjector, $this->workspaceProjector);

        $publishedEventsForContentStreamProjector = $this->filterPublishedEventsByListener(ContentStreamProjector::class);
        self::blockProjector($publishedEventsForContentStreamProjector, $this->contentStreamProjector);
    }

    protected static function blockProjector(DomainEvents $events, ProcessedEventsAwareProjectorInterface $projector): void
    {
        $attempts = 0;
        while (!$projector->hasProcessed($events)) {
            usleep(50000); // 50000μs = 50ms
            if (++$attempts > 300) { // 15 seconds
                $ids = '';
                foreach ($events as $p) {
                    $ids .= '   ' . $p->getIdentifier();
                }

                throw new \RuntimeException(sprintf('TIMEOUT while waiting for event(s) %s for projector "%s" - check the error logs for details.', $ids, get_class($projector)), 1550232279);
            }
        }
    }

    private function filterPublishedEventsByListener(string $listenerClassName): DomainEvents
    {
        $eventStoreId = $this->eventToListenerMappingProvider->getEventStoreIdentifierForListenerClassName($listenerClassName);
        $listenerMappings = $this->eventToListenerMappingProvider->getMappingsForEventStore($eventStoreId)->filter(static function (EventToListenerMapping $mapping) use ($listenerClassName) {
            return $mapping->getListenerClassName() === $listenerClassName;
        });
        $eventClassNames = [];
        foreach ($listenerMappings as $mapping) {
            $eventClassNames[$mapping->getEventClassName()] = true;
        }

        return $this->publishedEvents->filter(static function (DomainEventInterface $event) use ($eventClassNames) {
            if ($event instanceof DecoratedEvent) {
                $event = $event->getWrappedEvent();
            }
            return array_key_exists(get_class($event), $eventClassNames);
        });
    }
}
