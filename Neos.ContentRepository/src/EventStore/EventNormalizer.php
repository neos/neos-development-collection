<?php
declare(strict_types=1);
namespace Neos\ContentRepository\EventStore;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Event\ContentStreamWasAdded;
use Neos\ContentRepository\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Event\NodeWasCreated;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphProjection;
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
        return EventType::fromString((new \ReflectionClass($event))->getShortName());
    }

    public function denormalize(Event $event): EventInterface
    {
        try {
            /** @var class-string<EventInterface> $eventClassName */
            $eventClassName = match ($event->type->value) {
                'ContentStreamWasAdded' => ContentStreamWasAdded::class,
                'ContentStreamWasRemoved' => ContentStreamWasRemoved::class,
                'NodeWasCreated' => NodeWasCreated::class,
            };
        } catch (\UnhandledMatchError $exception) {
            throw new \InvalidArgumentException(sprintf('Failed to denormalize event "%s" of type "%s"', $event->id->value, $event->type->value), 1651839705);
        }
        try {
            $eventDataAsArray = json_decode($event->data->value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException(sprintf('Failed to decode data of event "%s": %s', $event->id->value, $exception->getMessage()), 1651839461);
        }
        assert(is_array($eventDataAsArray));
        return $eventClassName::fromArray($eventDataAsArray);
    }

}
