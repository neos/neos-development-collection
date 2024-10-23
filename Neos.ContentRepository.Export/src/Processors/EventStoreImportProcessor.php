<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export\Processors;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Events;
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
        private EventNormalizer $eventNormalizer,
        private ContentRepository $contentRepository,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        /** @var array<Event> $domainEvents */
        $domainEvents = [];
        $eventFileResource = $context->files->readStream('events.jsonl');

        /** @var array<string, string> $eventIdMap */
        $eventIdMap = [];

        $workspace = $this->contentRepository->findWorkspaceByName($this->targetWorkspaceName);
        if ($workspace === null) {
            throw new \InvalidArgumentException("Workspace {$this->targetWorkspaceName} does not exist", 1729530978);
        }

        while (($line = fgets($eventFileResource)) !== false) {
            $event =
                ExportedEvent::fromJson(trim($line))
                ->processPayload(fn (array $payload) => [...$payload, 'contentStreamId' => $workspace->currentContentStreamId->value, 'workspaceName' => $this->targetWorkspaceName->value]);
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
                        /** @var string|null $causationId */
                        $causationId = $processedMetadata['causationId'] ?? null;
                        if ($causationId !== null && array_key_exists($causationId, $eventIdMap)) {
                            $processedMetadata['causationId'] = $eventIdMap[$causationId];
                        }
                        /** @var string|null $correlationId */
                        $correlationId = $processedMetadata['correlationId'] ?? null;
                        if ($correlationId !== null && array_key_exists($correlationId, $eventIdMap)) {
                            $processedMetadata['correlationId'] = $eventIdMap[$correlationId];
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
            $domainEvent = DecoratedEvent::create($domainEvent, eventId: EventId::fromString($event->identifier), metadata: $event->metadata);
            $domainEvents[] = $this->eventNormalizer->normalize($domainEvent);
        }

        $contentStreamStreamName = ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)->getEventStreamName();
        try {
            $this->eventStore->commit($contentStreamStreamName, Events::fromArray($domainEvents), ExpectedVersion::ANY());
        } catch (ConcurrencyException $e) {
            throw new \RuntimeException(sprintf('Failed to publish %d events because the event stream "%s" already exists (3)', count($domainEvents), $contentStreamStreamName->value), 1729506818, $e);
        }
    }
}
