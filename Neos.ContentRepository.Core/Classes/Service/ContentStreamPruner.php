<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceEventStreamName;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Service\ContentStreamPruner\ContentStreamForPruning;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\EventType;
use Neos\EventStore\Model\Event\EventTypes;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventStream\EventStreamFilter;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\EventStore\Model\EventStream\VirtualStreamName;

/**
 * For implementation details of the content stream states and removed state, see {@see ContentStreamForPruning}.
 *
 * @api
 */
class ContentStreamPruner implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly EventStoreInterface $eventStore,
        private readonly EventNormalizer $eventNormalizer
    ) {
    }

    /**
     * Remove all content streams which are not needed anymore from the projections.
     *
     * NOTE: This still **keeps** the event stream as is; so it would be possible to re-construct the content stream
     *       at a later point in time (though we currently do not provide any API for it).
     *
     *       To remove the deleted Content Streams,
     *       call {@see ContentStreamPruner::pruneRemovedFromEventStream()} afterwards.
     *
     * By default, only content streams that are NO_LONGER_IN_USE will be removed.
     * If you also call with $removeTemporary=true, will delete ALL content streams which are currently not assigned
     * to a workspace (f.e. dangling ones in FORKED, CLOSED or CREATED.).
     *
     * @param bool $removeTemporary if TRUE, will delete ALL content streams not bound to a workspace
     */
    public function prune(bool $removeTemporary, \Closure $outputFn): void
    {
        $status = [ContentStreamStatus::NO_LONGER_IN_USE];
        if ($removeTemporary) {
            $status[] = ContentStreamStatus::CREATED;
            $status[] = ContentStreamStatus::FORKED;
            $status[] = ContentStreamStatus::CLOSED;
        }

        $allContentStreams = $this->getContentStreamsForPruning();

        $unusedContentStreamsPresent = false;
        foreach ($allContentStreams as $contentStream) {
            if (!in_array($contentStream->status, $status, true)) {
                continue;
            }

            $this->eventStore->commit(
                ContentStreamEventStreamName::fromContentStreamId($contentStream->id)->getEventStreamName(),
                $this->eventNormalizer->normalize(
                    new ContentStreamWasRemoved(
                        $contentStream->id
                    )
                ),
                ExpectedVersion::STREAM_EXISTS()
            );

            $outputFn(sprintf('Removed %s', $contentStream->id));

            $unusedContentStreamsPresent = true;
        }

        if ($unusedContentStreamsPresent) {
            try {
                $this->contentRepository->catchUpProjections();
            } catch (\Exception $e) {
                $outputFn(sprintf('Could not catchup after removing unused content streams: %s. You might need to use ./flow contentstream:pruneremovedfromeventstream and replay.', $e->getMessage()));
            }
        } else {
            $outputFn('There are no unused content streams.');
        }
    }

    /**
     * Remove unused and deleted content streams from the event stream; effectively REMOVING information completely.
     *
     * This is not so easy for nested workspaces / content streams:
     *   - As long as content streams are used as basis for others which are IN_USE_BY_WORKSPACE,
     *     these dependent Content Streams are not allowed to be removed in the event store.
     *
     *   - Otherwise, we cannot replay the other content streams correctly (if the base content streams are missing).
     *
     * @return list<ContentStreamId> the removed content streams
     */
    public function pruneRemovedFromEventStream(): array
    {
        $removedContentStreams = $this->findUnusedAndRemovedContentStreamIds();
        foreach ($removedContentStreams as $removedContentStream) {
            $this->eventStore->deleteStream(
                ContentStreamEventStreamName::fromContentStreamId(
                    $removedContentStream
                )->getEventStreamName()
            );
        }
        return $removedContentStreams;
    }

    public function pruneAll(): void
    {
        foreach ($this->findAllContentStreamEventNames() as $streamName) {
            $this->eventStore->deleteStream($streamName);
        }
    }

    /**
     * @return list<ContentStreamId>
     */
    private function findUnusedAndRemovedContentStreamIds(): array
    {
        $allContentStreams = $this->getContentStreamsForPruning();

        /** @var array<string,bool> $transitiveUsedStreams */
        $transitiveUsedStreams = [];
        /** @var list<ContentStreamId> $contentStreamIdsStack */
        $contentStreamIdsStack = [];

        // Step 1: Find all content streams currently in direct use by a workspace
        foreach ($allContentStreams as $stream) {
            if ($stream->status === ContentStreamStatus::IN_USE_BY_WORKSPACE && !$stream->removed) {
                $contentStreamIdsStack[] = $stream->id;
            }
        }

        // Step 2: When a content stream is in use by a workspace, its source content stream is also "transitively" in use.
        while ($contentStreamIdsStack !== []) {
            $currentStreamId = array_pop($contentStreamIdsStack);
            if (!array_key_exists($currentStreamId->value, $transitiveUsedStreams)) {
                $transitiveUsedStreams[$currentStreamId->value] = true;

                // Find source content streams for the current stream
                foreach ($allContentStreams as $stream) {
                    if ($stream->id === $currentStreamId && $stream->sourceContentStreamId !== null) {
                        $sourceStreamId = $stream->sourceContentStreamId;
                        if (!array_key_exists($sourceStreamId->value, $transitiveUsedStreams)) {
                            $contentStreamIdsStack[] = $sourceStreamId;
                        }
                    }
                }
            }
        }

        // Step 3: Check for removed content streams which we do not need anymore transitively
        $removedContentStreams = [];
        foreach ($allContentStreams as $contentStream) {
            if ($contentStream->removed && !array_key_exists($contentStream->id->value, $transitiveUsedStreams)) {
                $removedContentStreams[] = $contentStream->id;
            }
        }

        return $removedContentStreams;
    }

    /**
     * @return array<string, ContentStreamForPruning>
     */
    private function getContentStreamsForPruning(): array
    {
        $events = $this->eventStore->load(
            VirtualStreamName::forCategory(ContentStreamEventStreamName::EVENT_STREAM_NAME_PREFIX),
            EventStreamFilter::create(
                EventTypes::create(
                    EventType::fromString('ContentStreamWasCreated'),
                    EventType::fromString('ContentStreamWasForked'),
                    EventType::fromString('ContentStreamWasRemoved'),
                )
            )
        );

        /** @var array<string,ContentStreamForPruning> $status */
        $status = [];
        foreach ($events as $eventEnvelope) {
            $domainEvent = $this->eventNormalizer->denormalize($eventEnvelope->event);

            switch ($domainEvent::class) {
                case ContentStreamWasCreated::class:
                    $status[$domainEvent->contentStreamId->value] = ContentStreamForPruning::create(
                        $domainEvent->contentStreamId,
                        ContentStreamStatus::CREATED,
                        null
                    );
                    break;
                case ContentStreamWasForked::class:
                    $status[$domainEvent->newContentStreamId->value] = ContentStreamForPruning::create(
                        $domainEvent->newContentStreamId,
                        ContentStreamStatus::FORKED,
                        $domainEvent->sourceContentStreamId
                    );
                    break;
                case ContentStreamWasRemoved::class:
                    if (isset($status[$domainEvent->contentStreamId->value])) {
                        $status[$domainEvent->contentStreamId->value] = $status[$domainEvent->contentStreamId->value]
                            ->withRemoved();
                    }
                    break;
                default:
                    throw new \RuntimeException(sprintf('Unhandled event %s', $eventEnvelope->event->type->value));
            }
        }

        $workspaceEvents = $this->eventStore->load(
            VirtualStreamName::forCategory(WorkspaceEventStreamName::EVENT_STREAM_NAME_PREFIX),
            EventStreamFilter::create(
                EventTypes::create(
                    EventType::fromString('WorkspaceWasCreated'),
                    EventType::fromString('WorkspaceWasDiscarded'),
                    EventType::fromString('WorkspaceWasPartiallyDiscarded'),
                    EventType::fromString('WorkspaceWasPartiallyPublished'),
                    EventType::fromString('WorkspaceWasPublished'),
                    EventType::fromString('WorkspaceWasRebased'),
                    // we don't need to track WorkspaceWasRemoved as a ContentStreamWasRemoved event would be emitted before
                )
            )
        );
        foreach ($workspaceEvents as $eventEnvelope) {
            $domainEvent = $this->eventNormalizer->denormalize($eventEnvelope->event);

            switch ($domainEvent::class) {
                case WorkspaceWasCreated::class:
                    if (isset($status[$domainEvent->newContentStreamId->value])) {
                        $status[$domainEvent->newContentStreamId->value] = $status[$domainEvent->newContentStreamId->value]
                                ->withStatus(ContentStreamStatus::IN_USE_BY_WORKSPACE);
                    }
                    break;
                case WorkspaceWasDiscarded::class:
                    if (isset($status[$domainEvent->newContentStreamId->value])) {
                        $status[$domainEvent->newContentStreamId->value] = $status[$domainEvent->newContentStreamId->value]
                            ->withStatus(ContentStreamStatus::IN_USE_BY_WORKSPACE);
                    }
                    if (isset($status[$domainEvent->previousContentStreamId->value])) {
                        $status[$domainEvent->previousContentStreamId->value] = $status[$domainEvent->previousContentStreamId->value]
                            ->withStatus(ContentStreamStatus::NO_LONGER_IN_USE);
                    }
                    break;
                case WorkspaceWasPartiallyDiscarded::class:
                    if (isset($status[$domainEvent->newContentStreamId->value])) {
                        $status[$domainEvent->newContentStreamId->value] = $status[$domainEvent->newContentStreamId->value]
                            ->withStatus(ContentStreamStatus::IN_USE_BY_WORKSPACE);
                    }
                    if (isset($status[$domainEvent->previousContentStreamId->value])) {
                        $status[$domainEvent->previousContentStreamId->value] = $status[$domainEvent->previousContentStreamId->value]
                            ->withStatus(ContentStreamStatus::NO_LONGER_IN_USE);
                    }
                    break;
                case WorkspaceWasPartiallyPublished::class:
                    if (isset($status[$domainEvent->newSourceContentStreamId->value])) {
                        $status[$domainEvent->newSourceContentStreamId->value] = $status[$domainEvent->newSourceContentStreamId->value]
                            ->withStatus(ContentStreamStatus::IN_USE_BY_WORKSPACE);
                    }
                    if (isset($status[$domainEvent->previousSourceContentStreamId->value])) {
                        $status[$domainEvent->previousSourceContentStreamId->value] = $status[$domainEvent->previousSourceContentStreamId->value]
                            ->withStatus(ContentStreamStatus::NO_LONGER_IN_USE);
                    }
                    break;
                case WorkspaceWasRebased::class:
                    if (isset($status[$domainEvent->newContentStreamId->value])) {
                        $status[$domainEvent->newContentStreamId->value] = $status[$domainEvent->newContentStreamId->value]
                            ->withStatus(ContentStreamStatus::IN_USE_BY_WORKSPACE);
                    }
                    if (isset($status[$domainEvent->previousContentStreamId->value])) {
                        $status[$domainEvent->previousContentStreamId->value] = $status[$domainEvent->previousContentStreamId->value]
                            ->withStatus(ContentStreamStatus::NO_LONGER_IN_USE);
                    }
                    break;
                default:
                    throw new \RuntimeException(sprintf('Unhandled event %s', $eventEnvelope->event->type->value));
            }
        }
        return $status;
    }

    /**
     * @return list<StreamName>
     */
    private function findAllContentStreamEventNames(): array
    {
        $events = $this->eventStore->load(
            VirtualStreamName::forCategory(ContentStreamEventStreamName::EVENT_STREAM_NAME_PREFIX),
            EventStreamFilter::create(
                EventTypes::create(
                    EventType::fromString('ContentStreamWasCreated'),
                    EventType::fromString('ContentStreamWasForked')
                )
            )
        );
        $allContentStreamEventStreamNames = [];
        foreach ($events as $eventEnvelope) {
            $allContentStreamEventStreamNames[$eventEnvelope->streamName->value] = true;
        }
        return array_map(StreamName::fromString(...), array_keys($allContentStreamEventStreamNames));
    }
}
