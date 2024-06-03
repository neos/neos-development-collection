<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasReopened;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateDimensionsWereUpdated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceBaseWorkspaceWasChanged;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceOwnerWasChanged;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceWasRenamed;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventData;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventType;

/**
 * Central authority to convert Content Repository domain events to Event Store EventData and EventType, vice versa.
 *
 * For normalizing (from classes to event store), this is called from {@see ContentRepository::normalizeEvent()}.
 *
 * For denormalizing (from event store to classes), this is called in the individual projections; f.e.
 * {@see ProjectionInterface::apply()}.
 *
 * @api because inside projections, you get an instance of EventNormalizer to handle events.
 */
final class EventNormalizer
{
    /**
     * @var array<class-string<EventInterface>,EventType>
     */
    private array $fullClassNameToShortEventType = [];
    /**
     * @var array<string,class-string<EventInterface>>
     */
    private array $shortEventTypeToFullClassName = [];

    /**
     * @internal never instanciate this object yourself
     */
    public function __construct()
    {
        $supportedEventClassNames = [
            ContentStreamWasClosed::class,
            ContentStreamWasCreated::class,
            ContentStreamWasForked::class,
            ContentStreamWasReopened::class,
            ContentStreamWasRemoved::class,
            DimensionShineThroughWasAdded::class,
            DimensionSpacePointWasMoved::class,
            NodeAggregateNameWasChanged::class,
            NodeAggregateTypeWasChanged::class,
            NodeAggregateWasDisabled::class,
            NodeAggregateWasEnabled::class,
            NodeAggregateWasMoved::class,
            NodeAggregateWasRemoved::class,
            NodeAggregateWithNodeWasCreated::class,
            NodeGeneralizationVariantWasCreated::class,
            NodePeerVariantWasCreated::class,
            NodePropertiesWereSet::class,
            NodeReferencesWereSet::class,
            NodeSpecializationVariantWasCreated::class,
            RootNodeAggregateWithNodeWasCreated::class,
            RootWorkspaceWasCreated::class,
            RootNodeAggregateDimensionsWereUpdated::class,
            SubtreeWasTagged::class,
            SubtreeWasUntagged::class,
            WorkspaceRebaseFailed::class,
            WorkspaceWasCreated::class,
            WorkspaceWasRenamed::class,
            WorkspaceWasDiscarded::class,
            WorkspaceWasPartiallyDiscarded::class,
            WorkspaceWasPartiallyPublished::class,
            WorkspaceWasPublished::class,
            WorkspaceWasRebased::class,
            WorkspaceWasRemoved::class,
            WorkspaceOwnerWasChanged::class,
            WorkspaceBaseWorkspaceWasChanged::class,
        ];

        foreach ($supportedEventClassNames as $fullEventClassName) {
            $shortEventClassName = substr($fullEventClassName, strrpos($fullEventClassName, '\\') + 1);

            $this->fullClassNameToShortEventType[$fullEventClassName] = EventType::fromString($shortEventClassName);
            $this->shortEventTypeToFullClassName[$shortEventClassName] = $fullEventClassName;
        }
    }

    /**
     * @return class-string<EventInterface>
     */
    public function getEventClassName(Event $event): string
    {
        return $this->shortEventTypeToFullClassName[$event->type->value] ?? throw new \InvalidArgumentException(
            sprintf('Failed to denormalize event "%s" of type "%s"', $event->id->value, $event->type->value),
            1651839705
        );
    }

    public function normalize(EventInterface|DecoratedEvent $event): Event
    {
        $eventId = $event instanceof DecoratedEvent && $event->eventId !== null ? $event->eventId : EventId::create();
        $eventMetadata = $event instanceof DecoratedEvent ? $event->eventMetadata : null;
        $causationId = $event instanceof DecoratedEvent ? $event->causationId : null;
        $correlationId = $event instanceof DecoratedEvent ? $event->correlationId : null;
        $event = $event instanceof DecoratedEvent ? $event->innerEvent : $event;
        return new Event(
            $eventId,
            $this->getEventType($event),
            $this->getEventData($event),
            $eventMetadata,
            $causationId,
            $correlationId,
        );
    }

    public function denormalize(Event $event): EventInterface
    {
        $eventClassName = $this->getEventClassName($event);
        try {
            $eventDataAsArray = json_decode($event->data->value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException(
                sprintf('Failed to decode data of event with type "%s" and id "%s": %s', $event->type->value, $event->id->value, $exception->getMessage()),
                1651839461
            );
        }
        if (!is_array($eventDataAsArray)) {
            throw new \RuntimeException(sprintf('Expected array got %s', $eventDataAsArray));
        }
        /** {@see EventInterface::fromArray()} */
        $eventInstance = $eventClassName::fromArray($eventDataAsArray);
        return match ($eventInstance::class) {
            // upcast disabled / enabled events to the corresponding SubtreeTag events
            NodeAggregateWasDisabled::class => new SubtreeWasTagged($eventInstance->workspaceName, $eventInstance->contentStreamId, $eventInstance->nodeAggregateId, $eventInstance->affectedDimensionSpacePoints, SubtreeTag::disabled()),
            NodeAggregateWasEnabled::class => new SubtreeWasUntagged($eventInstance->workspaceName, $eventInstance->contentStreamId, $eventInstance->nodeAggregateId, $eventInstance->affectedDimensionSpacePoints, SubtreeTag::disabled()),
            default => $eventInstance,
        };
    }

    private function getEventData(EventInterface $event): EventData
    {
        try {
            $eventDataAsJson = json_encode($event, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Failed to normalize event of type "%s": %s',
                    get_debug_type($event),
                    $exception->getMessage()
                ),
                1651838981
            );
        }
        return EventData::fromString($eventDataAsJson);
    }

    private function getEventType(EventInterface $event): EventType
    {
        $className = get_class($event);

        return $this->fullClassNameToShortEventType[$className] ?? throw new \RuntimeException(
            'Event type ' . get_class($event) . ' not registered'
        );
    }
}
