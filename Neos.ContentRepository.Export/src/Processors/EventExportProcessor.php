<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Processors;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\ProcessorResult;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\StreamName;

/**
 * Processor that exports all events of the live workspace to an "events.jsonl" file
 */
final class EventExportProcessor implements ProcessorInterface
{
    private array $callbacks = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly EventStoreInterface $eventStore,
    ) {}

    public function onMessage(\Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }


    public function run(): ProcessorResult
    {
        $liveWorkspace = $this->workspaceFinder->findOneByName(WorkspaceName::forLive());
        if ($liveWorkspace === null) {
            return ProcessorResult::error('Failed to find live workspace');
        }
        $streamName = StreamName::fromString(
            'Neos.ContentRepository:ContentStream:' . $liveWorkspace->currentContentStreamId
        );
        $eventStream = $this->eventStore->load($streamName);

        $eventFileResource = fopen('php://temp/maxmemory:5242880', 'rb+');
        if ($eventFileResource === false) {
            return ProcessorResult::error('Failed to create temporary event file resource');
        }

        $numberOfExportedEvents = 0;
        foreach ($eventStream as $eventEnvelope) {
            $event = ExportedEvent::fromRawEvent($eventEnvelope->event);
            fwrite($eventFileResource, $event->toJson() . chr(10));
            $numberOfExportedEvents ++;
        }
        try {
            $this->files->writeStream('events.jsonl', $eventFileResource);
        } catch (FilesystemException $e) {
            return ProcessorResult::error(sprintf('Failed to write events.jsonl: %s', $e->getMessage()));
        }
        fclose($eventFileResource);
        return ProcessorResult::success(sprintf('Exported %d event%s', $numberOfExportedEvents, $numberOfExportedEvents === 1 ? '' : 's'));
    }

    /** --------------------------------------- */


    private function dispatch(Severity $severity, string $message, mixed ...$args): void
    {
        $renderedMessage = sprintf($message, ...$args);
        foreach ($this->callbacks as $callback) {
            $callback($severity, $renderedMessage);
        }
    }
}
