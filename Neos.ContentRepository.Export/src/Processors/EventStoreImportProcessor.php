<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Processors;

use League\Flysystem\Filesystem;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\ProcessorResult;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\ContentRepository\EventStore\EventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\Exception\ConcurrencyException;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Utility\Algorithms;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;

/**
 * Processor that imports all events from an "events.jsonl" file to the event store
 */
final class EventStoreImportProcessor implements ProcessorInterface
{
    private array $callbacks = [];

    public function __construct(
        private readonly bool $keepEventIds,
        private readonly bool $keepStreamName,
        private readonly Filesystem $files,
        private readonly EventStore $eventStore,
        private readonly EventNormalizer $eventNormalizer,

    ) {}

    public function onMessage(\Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    public function run(): ProcessorResult
    {
        /** @var array<DomainEventInterface> $domainEvents */
        $domainEvents = [];
        $eventFileResource = $this->files->readStream('events.jsonl');

        /** @var ContentStreamIdentifier|null $contentStreamIdentifier */
        $contentStreamIdentifier = null;

        /** @var array<string, string> $eventIdMap */
        $eventIdMap = [];

        while (($line = fgets($eventFileResource)) !== false) {
            $event = ExportedEvent::fromJson(trim($line));
            if ($contentStreamIdentifier === null) {
                $contentStreamIdentifier = $this->keepStreamName ? self::extractContentStreamIdentifier($event->payload) : ContentStreamIdentifier::create();
            }
            if (!$this->keepStreamName) {
                $event = $event->processPayload(fn(array $payload) => isset($payload['contentStreamIdentifier']) ? [...$payload, 'contentStreamIdentifier' => (string)$contentStreamIdentifier] : $payload);
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
                        $causationId = $processedMetadata['causationIdentifier'] ?? null;
                        if ($causationId !== null && array_key_exists($causationId, $eventIdMap)) {
                            $processedMetadata['causationIdentifier'] = $eventIdMap[$causationId];
                        }
                        /** @var string|null $correlationId */
                        $correlationId = $processedMetadata['correlationIdentifier'] ?? null;
                        if ($correlationId !== null && array_key_exists($correlationId, $eventIdMap)) {
                            $processedMetadata['correlationIdentifier'] = $eventIdMap[$correlationId];
                        }
                        return $processedMetadata;
                    });
            }
            try {
                $domainEvent = $this->eventNormalizer->denormalize($event->payload, $event->type);
            } catch (SerializerException $e) {
                throw new \RuntimeException(sprintf('Failed to denormalize event "%s" of type "%s": %s', $event->identifier, $event->type, $e->getMessage()), 1646328800, $e);
            }
            $domainEvent = DecoratedEvent::addIdentifier($domainEvent, $event->identifier);
            if ($event->metadata !== null && $event->metadata !== []) {
                $domainEvent = DecoratedEvent::addMetadata($domainEvent, $event->metadata);
            }
            $domainEvents[] = $domainEvent;
        }

        $workspaceName = WorkspaceName::forLive();
        $workspaceStreamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $workspaceName->name);
        $events = DomainEvents::withSingleEvent(
            new RootWorkspaceWasCreated(
                $workspaceName,
                WorkspaceTitle::fromString('live workspace'),
                WorkspaceDescription::fromString('live workspace'),
                UserIdentifier::forSystemUser(),
                $contentStreamIdentifier
            ),
        );
        try {
            $this->eventStore->commit($workspaceStreamName, $events, ExpectedVersion::NO_STREAM);
        } catch (ConcurrencyException $e) {
            return ProcessorResult::error(sprintf('Failed to publish workspace events because the event stream "%s" already exists', $workspaceStreamName));
        }

        $streamName = StreamName::fromString('Neos.ContentRepository:ContentStream:' . $contentStreamIdentifier);
        try {
            $this->eventStore->commit($streamName, DomainEvents::fromArray($domainEvents), ExpectedVersion::NO_STREAM);
        } catch (ConcurrencyException $e) {
            return ProcessorResult::error(sprintf('Failed to publish %d events because the event stream "%s" already exists', count($domainEvents), $streamName));
        }
        return ProcessorResult::success(sprintf('Imported %d event%s into stream "%s"', count($domainEvents), count($domainEvents) === 1 ? '' : 's', $streamName));
    }

    /** --------------------------- */

    /**
     * @param array<string, mixed> $payload
     * @return ContentStreamIdentifier
     */
    private static function extractContentStreamIdentifier(array $payload): ContentStreamIdentifier
    {
        if (!isset($payload['contentStreamIdentifier']) || !is_string($payload['contentStreamIdentifier'])) {
            throw new \RuntimeException('Failed to extract "contentStreamIdentifier" from event', 1646404169);
        }
        return ContentStreamIdentifier::fromString($payload['contentStreamIdentifier']);
    }

    private function dispatch(Severity $severity, string $message, mixed ...$args): void
    {
        $renderedMessage = sprintf($message, ...$args);
        foreach ($this->callbacks as $callback) {
            $callback($severity, $renderedMessage);
        }
    }
}
