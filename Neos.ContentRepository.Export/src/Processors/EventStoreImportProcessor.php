<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export\Processors;

use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventType;
use Neos\EventStore\Model\Event\EventTypes;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\Events;
use Neos\EventStore\Model\EventStream\EventStreamFilter;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\Flow\Utility\Algorithms;

/**
 * Processor that imports all events from an "events.jsonl" file to the event store
 */
final readonly class EventStoreImportProcessor implements ProcessorInterface, ContentRepositoryServiceInterface
{
    public function __construct(
        private WorkspaceName $targetWorkspaceName,
        private bool $keepEventIds,
        private EventStoreInterface $eventStore,
        private EventNormalizer $eventNormalizer
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        /** @var array<Event> $events */
        $events = [];
        $eventFileResource = $context->files->readStream('events.jsonl');

        /** @var array<string, string> $eventIdMap */
        $eventIdMap = [];

        $rootWorkspaceContentStreamId = null;
        foreach ($this->eventStore->load(
            WorkspaceEventStreamName::fromWorkspaceName($this->targetWorkspaceName)->getEventStreamName(),
            EventStreamFilter::create(EventTypes::create(EventType::fromString('RootWorkspaceWasCreated')))
        ) as $eventEnvelope) {
            $rootWorkspaceWasCreatedEvent = $this->eventNormalizer->denormalize($eventEnvelope->event);
            if (!$rootWorkspaceWasCreatedEvent instanceof RootWorkspaceWasCreated) {
                throw new \RuntimeException(sprintf('Expected event of type %s got %s', RootWorkspaceWasCreated::class, $rootWorkspaceWasCreatedEvent::class), 1732109840);
            }
            $rootWorkspaceContentStreamId = $rootWorkspaceWasCreatedEvent->newContentStreamId;
            break;
        }

        if ($rootWorkspaceContentStreamId === null) {
            throw new \InvalidArgumentException(sprintf('Workspace "%s" does not exist or is not a root workspace', $this->targetWorkspaceName), 1729530978);
        }

        $correlationId = Event\CorrelationId::fromString(sprintf('EventStoreImporter_%s', bin2hex(random_bytes(9))));
        while (($line = fgets($eventFileResource)) !== false) {
            $event =
                ExportedEvent::fromJson(trim($line))
                ->processPayload(fn (array $payload) => [...$payload, 'contentStreamId' => $rootWorkspaceContentStreamId->value, 'workspaceName' => $this->targetWorkspaceName->value]);
            if (!$this->keepEventIds) {
                try {
                    $newEventId = Algorithms::generateUUID();
                } catch (\Exception $e) {
                    throw new \RuntimeException(sprintf('Failed to create new event identifier: %s', $e->getMessage()), 1646386859, $e);
                }
                $eventIdMap[$event->identifier] = $newEventId;
                $event = $event
                    ->withIdentifier($newEventId)
                    ->processMetadata(static function (array $metadata) use ($eventIdMap) {
                        $processedMetadata = $metadata;
                        // todo the causationId is NOT written to the EventMetadata anymore but a dedicated field and must be exported separately
                        /** @var string|null $causationId */
                        $causationId = $processedMetadata['causationId'] ?? null;
                        if ($causationId !== null && array_key_exists($causationId, $eventIdMap)) {
                            $processedMetadata['causationId'] = $eventIdMap[$causationId];
                        }
                        return $processedMetadata;
                    });
            }
            $domainEvent = $this->eventNormalizer->denormalize(
                new Event(
                    EventId::fromString($event->identifier),
                    Event\EventType::fromString($event->type),
                    Event\EventData::fromString(\json_encode($event->payload, JSON_THROW_ON_ERROR)),
                    Event\EventMetadata::fromArray($event->metadata)
                )
            );
            if (in_array($domainEvent::class, [ContentStreamWasCreated::class, ContentStreamWasForked::class, ContentStreamWasRemoved::class], true)) {
                throw new \RuntimeException(sprintf('Failed to read events. %s is not expected in imported event stream.', $event->type), 1729506757);
            }
            $domainEvent = DecoratedEvent::create($domainEvent, eventId: EventId::fromString($event->identifier), metadata: $event->metadata, correlationId: $correlationId);
            $events[] = $this->eventNormalizer->normalize($domainEvent);
        }

        $contentStreamStreamName = ContentStreamEventStreamName::fromContentStreamId($rootWorkspaceContentStreamId)->getEventStreamName();
        try {
            $this->eventStore->commit($contentStreamStreamName, Events::fromArray($events), ExpectedVersion::fromVersion(Version::first()));
        } catch (ConcurrencyException $e) {
            throw new \RuntimeException(sprintf('Failed to publish %d events because the content stream "%s" for workspace "%s" already contains events. Please consider to prune the content repository first via `./flow site:pruneAll`.', count($events), $contentStreamStreamName->value, $this->targetWorkspaceName->value), 1729506818, $e);
        }
    }
}
