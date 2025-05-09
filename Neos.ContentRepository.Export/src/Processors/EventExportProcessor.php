<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export\Processors;

use League\Flysystem\FilesystemException;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\EventStore\EventStoreInterface;

/**
 * Processor that exports all events of the live workspace to an "events.jsonl" file
 */
final readonly class EventExportProcessor implements ProcessorInterface, ContentRepositoryServiceInterface
{
    /**
     * @param ContentStreamId $contentStreamId Identifier of the content stream to export
     */
    public function __construct(
        private ContentStreamId $contentStreamId,
        private EventStoreInterface $eventStore,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $streamName = ContentStreamEventStreamName::fromContentStreamId($this->contentStreamId)->getEventStreamName();
        $eventStream = $this->eventStore->load($streamName);

        $eventFileResource = fopen('php://temp/maxmemory:5242880', 'rb+');
        if ($eventFileResource === false) {
            throw new \RuntimeException('Failed to create temporary event file resource', 1729506599);
        }

        foreach ($eventStream as $eventEnvelope) {
            if ($eventEnvelope->event->type->value === 'ContentStreamWasCreated') {
                // the content stream will be created in the import dynamically, so we prevent duplication here
                continue;
            }
            $event = ExportedEvent::fromRawEvent($eventEnvelope->event);
            fwrite($eventFileResource, $event->toJson() . chr(10));
        }
        try {
            $context->files->writeStream('events.jsonl', $eventFileResource);
        } catch (FilesystemException $e) {
            throw new \RuntimeException(sprintf('Failed to write events.jsonl: %s', $e->getMessage()), 1729506623, $e);
        }
        fclose($eventFileResource);
    }
}
