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
final class EventStoreImportProcessor implements ProcessorInterface, ContentRepositoryServiceInterface
{
    private ?ContentStreamId $contentStreamId = null;

    public function __construct(
        private readonly bool $keepEventIds,
        private readonly EventStoreInterface $eventStore,
        private readonly EventNormalizer $eventNormalizer,
        ?ContentStreamId $overrideContentStreamId
    ) {
        if ($overrideContentStreamId) {
            $this->contentStreamId = $overrideContentStreamId;
        }
    }

    public function run(ProcessingContext $context): void
    {
        /** @var array<Event> $domainEvents */
        $domainEvents = [];
        $eventFileResource = $context->files->readStream('events.jsonl');

        /** @var array<string, string> $eventIdMap */
        $eventIdMap = [];

        $keepStreamName = false;
        while (($line = fgets($eventFileResource)) !== false) {
            $event = ExportedEvent::fromJson(trim($line));
            if ($this->contentStreamId === null) {
                $this->contentStreamId = self::extractContentStreamId($event->payload);
                $keepStreamName = true;
            }
            if (!$keepStreamName) {
                $event = $event->processPayload(fn(array $payload) => isset($payload['contentStreamId']) ? [...$payload, 'contentStreamId' => $this->contentStreamId->value] : $payload);
            }
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

        assert($this->contentStreamId !== null);

        $contentStreamStreamName = ContentStreamEventStreamName::fromContentStreamId($this->contentStreamId)->getEventStreamName();
        $events = Events::with(
            $this->eventNormalizer->normalize(
                new ContentStreamWasCreated(
                    $this->contentStreamId,
                )
            )
        );
        try {
            $contentStreamCreationCommitResult = $this->eventStore->commit($contentStreamStreamName, $events, ExpectedVersion::NO_STREAM());
        } catch (ConcurrencyException $e) {
            throw new \RuntimeException(sprintf('Failed to publish workspace events because the event stream "%s" already exists (1)', $this->contentStreamId->value), 1729506776, $e);
        }

        $workspaceName = WorkspaceName::forLive();
        $workspaceStreamName = WorkspaceEventStreamName::fromWorkspaceName($workspaceName)->getEventStreamName();
        $events = Events::with(
            $this->eventNormalizer->normalize(
                new RootWorkspaceWasCreated(
                    $workspaceName,
                    $this->contentStreamId
                )
            )
        );
        try {
            $this->eventStore->commit($workspaceStreamName, $events, ExpectedVersion::NO_STREAM());
        } catch (ConcurrencyException $e) {
            throw new \RuntimeException(sprintf('Failed to publish workspace events because the event stream "%s" already exists (2)', $workspaceStreamName->value), 1729506798, $e);
        }

        try {
            $this->eventStore->commit($contentStreamStreamName, Events::fromArray($domainEvents), ExpectedVersion::fromVersion($contentStreamCreationCommitResult->highestCommittedVersion));
        } catch (ConcurrencyException $e) {
            throw new \RuntimeException(sprintf('Failed to publish %d events because the event stream "%s" already exists (3)', count($domainEvents), $contentStreamStreamName->value), 1729506818, $e);
        }
    }

    /** --------------------------- */

    /**
     * @param array<string, mixed> $payload
     * @return ContentStreamId
     */
    private static function extractContentStreamId(array $payload): ContentStreamId
    {
        if (!isset($payload['contentStreamId']) || !is_string($payload['contentStreamId'])) {
            throw new \RuntimeException('Failed to extract "contentStreamId" from event', 1646404169);
        }
        return ContentStreamId::fromString($payload['contentStreamId']);
    }
}
