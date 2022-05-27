<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Event;

use League\Flysystem\FilesystemException;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ESCR\Export\Middleware\Context;
use Neos\ESCR\Export\Middleware\Event\ValueObject\Attributes;
use Neos\ESCR\Export\Middleware\Event\ValueObject\ExportedEvent;
use Neos\ESCR\Export\Middleware\MiddlewareInterface;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Utility\Algorithms;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;

final class NeosEventMiddleware implements MiddlewareInterface
{
    private const EVENT_STORE_ID = 'ContentRepository';

    public function __construct(
        private readonly bool $keepEventIds,
        private readonly bool $keepStreamName,
        private readonly EventStoreFactory $eventStoreFactory,
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly EventNormalizer $eventNormalizer,
    ) {}

    public function getLabel(): string
    {
        return 'Neos Content Repository events';
    }

    public function processImport(Context $context): void
    {
        $eventStore = $this->getEventStore();
        /** @var array<DomainEventInterface> $domainEvents */
        $domainEvents = [];
        $eventFileResource = $context->files->readStream('events.jsonl');
        $attributes = Attributes::create()->withMetadata()->withRecordedAt();

        /** @var ContentStreamIdentifier|null $contentStreamIdentifier */
        $contentStreamIdentifier = null;

        /** @var array<string, string> $eventIdMap */
        $eventIdMap = [];

        while (($line = fgets($eventFileResource)) !== false) {
            $event = ExportedEvent::fromJson(trim($line), $attributes);
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
        $eventStore->commit($workspaceStreamName, $events, ExpectedVersion::NO_STREAM);

        $streamName = StreamName::fromString('Neos.ContentRepository:ContentStream:' . $contentStreamIdentifier);
        $eventStore->commit($streamName, DomainEvents::fromArray($domainEvents), ExpectedVersion::NO_STREAM);
        $context->report(sprintf('Imported %d event%s into stream "%s"', count($domainEvents), count($domainEvents) === 1 ? '' : 's', $streamName));
    }

    public function processExport(Context $context): void
    {
        $liveWorkspace = $this->workspaceFinder->findOneByName(WorkspaceName::forLive());
        if ($liveWorkspace === null) {
            throw new \RuntimeException('Failed to find live workspace', 1646319944);
        }
        $streamName = StreamName::fromString('Neos.ContentRepository:ContentStream:' . $liveWorkspace->getCurrentContentStreamIdentifier());
        $eventStream = $this->getEventStore()->load($streamName);
        $attributes = Attributes::create()->withMetadata()->withRecordedAt();

        $eventFileResource = fopen('php://temp/maxmemory:5242880', 'rb+');
        if ($eventFileResource === false) {
            throw new \RuntimeException('Failed to create temporary event file resource', 1646326820);
        }

        $numberOfExportedEvents = 0;
        foreach ($eventStream as $eventEnvelope) {
            $event = ExportedEvent::fromRawEvent($eventEnvelope->getRawEvent(), $attributes);
            fwrite($eventFileResource, $event->toJson() . chr(10));
            $numberOfExportedEvents ++;
        }
        try {
            $context->files->writeStream('events.jsonl', $eventFileResource);
        } catch (FilesystemException $e) {
            throw new \RuntimeException(sprintf('Failed to write events.jsonl: %s', $e->getMessage()), 1646326885, $e);
        }
        fclose($eventFileResource);
        $context->report(sprintf('Exported %d event%s', $numberOfExportedEvents, $numberOfExportedEvents === 1 ? '' : 's'));
    }

    /** --------------------------- */

    private function getEventStore(): EventStore
    {
        try {
            return $this->eventStoreFactory->create(self::EVENT_STORE_ID);
            /** @phpstan-ignore-next-line */
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException(sprintf('Failed instantiate Event Store instance "%s": %s', self::EVENT_STORE_ID, $e->getMessage()), 1646319910, $e);
        }
    }

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
}
