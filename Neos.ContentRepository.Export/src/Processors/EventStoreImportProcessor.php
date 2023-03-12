<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Processors;

use League\Flysystem\Filesystem;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\EventPersister;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\ProcessorResult;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\Events;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\Flow\Utility\Algorithms;

/**
 * Processor that imports all events from an "events.jsonl" file to the event store
 */
final class EventStoreImportProcessor implements ProcessorInterface
{
    private array $callbacks = [];

    public function __construct(
        private readonly bool $keepEventIds,
        private readonly Filesystem $files,
        private readonly EventStoreInterface $eventStore,
        private readonly EventNormalizer $eventNormalizer,
        private ?ContentStreamId $contentStreamId,
    ) {}

    public function onMessage(\Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    public function run(): ProcessorResult
    {
        /** @var array<Event> $domainEvents */
        $domainEvents = [];
        $eventFileResource = $this->files->readStream('events.jsonl');

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
                $event = $event->processPayload(fn(array $payload) => isset($payload['contentStreamId']) ? [...$payload, 'contentStreamId' => (string)$this->contentStreamId] : $payload);
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
                    ->processMetadata(static function(array $metadata) use ($eventIdMap) {
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
                    Event\EventData::fromString(\json_encode($event->payload)),
                    Event\EventMetadata::fromArray($event->metadata)
                )
            );
            $domainEvent = DecoratedEvent::withEventId($domainEvent, EventId::fromString($event->identifier));
            if ($event->metadata !== null && $event->metadata !== []) {
                $domainEvent = DecoratedEvent::withMetadata($domainEvent, EventMetadata::fromArray($event->metadata));
            }
            $domainEvents[] = $this->normalizeEvent($domainEvent);
        }

        assert($this->contentStreamId !== null);

        $contentStreamStreamName = StreamName::fromString('ContentStream:' . $this->contentStreamId);
        $events = Events::with(
            $this->normalizeEvent(
                new ContentStreamWasCreated(
                    $this->contentStreamId,
                )
            )
        );
        try {
            $contentStreamCreationCommitResult = $this->eventStore->commit($contentStreamStreamName, $events, ExpectedVersion::NO_STREAM());
        } catch (ConcurrencyException $e) {
            return ProcessorResult::error(sprintf('Failed to publish workspace events because the event stream "%s" already exists', $this->contentStreamId->value));
        }

        $workspaceName = WorkspaceName::forLive();
        $workspaceStreamName = StreamName::fromString('Workspace:' . $workspaceName->name);
        $events = Events::with(
            $this->normalizeEvent(
                new RootWorkspaceWasCreated(
                    $workspaceName,
                    WorkspaceTitle::fromString('live workspace'),
                    WorkspaceDescription::fromString('live workspace'),
                    $this->contentStreamId
                )
            )
        );
        try {
            $this->eventStore->commit($workspaceStreamName, $events, ExpectedVersion::NO_STREAM());
        } catch (ConcurrencyException $e) {
            return ProcessorResult::error(sprintf('Failed to publish workspace events because the event stream "%s" already exists', $workspaceStreamName->value));
        }


        try {
            $this->eventStore->commit($contentStreamStreamName, Events::fromArray($domainEvents), ExpectedVersion::fromVersion($contentStreamCreationCommitResult->highestCommittedVersion));
        } catch (ConcurrencyException $e) {
            throw $e;
            return ProcessorResult::error(sprintf('Failed to publish %d events because the event stream "%s" already exists', count($domainEvents), $contentStreamStreamName->value));
        }
        return ProcessorResult::success(sprintf('Imported %d event%s into stream "%s"', count($domainEvents), count($domainEvents) === 1 ? '' : 's', $contentStreamStreamName->value));
    }

    /**
     * Copied from {@see EventPersister::normalizeEvent()}
     *
     * @param EventInterface|DecoratedEvent $event
     * @return Event
     */
    private function normalizeEvent(EventInterface|DecoratedEvent $event): Event
    {
        if ($event instanceof DecoratedEvent) {
            $eventId = $event->eventId;
            $eventMetadata = $event->eventMetadata;
            $event = $event->innerEvent;
        } else {
            $eventId = EventId::create();
            $eventMetadata = EventMetadata::none();
        }
        return new Event(
            $eventId,
            $this->eventNormalizer->getEventType($event),
            $this->eventNormalizer->getEventData($event),
            $eventMetadata,
        );
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

    private function dispatch(Severity $severity, string $message, mixed ...$args): void
    {
        $renderedMessage = sprintf($message, ...$args);
        foreach ($this->callbacks as $callback) {
            $callback($severity, $renderedMessage);
        }
    }
}
