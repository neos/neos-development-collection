<?php
declare(strict_types=1);
namespace Neos\ContentRepository\EventStore;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\EventStore\Model\Event\EventData;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventType;

/**
 * Central authority to convert Content Repository domain events to Event Store EventData and EventType, vice versa.
 *
 * For normalizing (from classes to event store), this is called from {@see ContentRepository::normalizeEvent()}.
 *
 * For denormalizing (from event store to classes), this is called in the individual projections; f.e. {@see ContentGraphProjection::apply()}.
 */
final class EventNormalizer
{

    private array $fullClassNameToShortEventType = [];
    private array $shortEventTypeToFullClassName = [];

    public function __construct() {
        $supportedEventClassNames = [
            ContentStreamWasCreated::class,
            ContentStreamWasForked::class,
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
            WorkspaceRebaseFailed::class,
            WorkspaceWasCreated::class,
            WorkspaceWasDiscarded::class,
            WorkspaceWasPartiallyDiscarded::class,
            WorkspaceWasPartiallyPublished::class,
            WorkspaceWasPublished::class,
            WorkspaceWasRebased::class
        ];

        foreach ($supportedEventClassNames as $fullEventClassName) {
            $shortEventClassName = substr($fullEventClassName, strrpos($fullEventClassName, '\\') + 1);

            $this->fullClassNameToShortEventType[$fullEventClassName] = EventType::fromString($shortEventClassName);
            $this->shortEventTypeToFullClassName[$shortEventClassName] = $fullEventClassName;
        }
    }

    public function getEventData(EventInterface $event): EventData
    {
        try {
            $eventDataAsJson = json_encode($event, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException(sprintf('Failed to normalize event of type "%s": %s', get_debug_type($event), $exception->getMessage()), 1651838981);
        }
        return EventData::fromString($eventDataAsJson);
    }

    public function getEventType(EventInterface $event): EventType
    {
        $className = get_class($event);

        return $this->fullClassNameToShortEventType[$className] ?? throw new \RuntimeException('Event type ' . get_class($event) . ' not registered');
    }

    public function denormalize(Event $event): EventInterface
    {
        /** @var class-string<EventInterface> $eventClassName */
        $eventClassName = $this->shortEventTypeToFullClassName[$event->type->value] ?? throw new \InvalidArgumentException(sprintf('Failed to denormalize event "%s" of type "%s"', $event->id->value, $event->type->value), 1651839705);
        try {
            $eventDataAsArray = json_decode($event->data->value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException(sprintf('Failed to decode data of event "%s": %s', $event->id->value, $exception->getMessage()), 1651839461);
        }
        assert(is_array($eventDataAsArray));
        return $eventClassName::fromArray($eventDataAsArray);
    }
}
