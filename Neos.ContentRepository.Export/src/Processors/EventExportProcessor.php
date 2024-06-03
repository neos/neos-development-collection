<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Processors;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\ProcessorResult;
use Neos\ContentRepository\Export\Severity;
use Neos\EventStore\EventStoreInterface;

/**
 * Processor that exports all events of the live workspace to an "events.jsonl" file
 */
final class EventExportProcessor implements ProcessorInterface, ContentRepositoryServiceInterface
{
    /** @var array<int, \Closure> */
    private array $callbacks = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly EventStoreInterface $eventStore,
    ) {
    }

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
        $streamName = ContentStreamEventStreamName::fromContentStreamId($liveWorkspace->currentContentStreamId)->getEventStreamName();
        $eventStream = $this->eventStore->load($streamName);

        $eventFileResource = fopen('php://temp/maxmemory:5242880', 'rb+');
        if ($eventFileResource === false) {
            return ProcessorResult::error('Failed to create temporary event file resource');
        }

        $numberOfExportedEvents = 0;
        foreach ($eventStream as $eventEnvelope) {
            if ($eventEnvelope->event->type->value === 'ContentStreamWasCreated') {
                // the content stream will be created in the import dynamically, so we prevent duplication here
                continue;
            }
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


    /**
     * @phpstan-ignore-next-line currently this private method is unused ... but it does no harm keeping it
     */
    private function dispatch(Severity $severity, string $message, mixed ...$args): void
    {
        $renderedMessage = sprintf($message, ...$args);
        foreach ($this->callbacks as $callback) {
            $callback($severity, $renderedMessage);
        }
    }
}
