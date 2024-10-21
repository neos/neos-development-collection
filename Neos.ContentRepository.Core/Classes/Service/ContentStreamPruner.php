<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Command\RemoveContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreams;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;
use Neos\EventStore\EventStoreInterface;

/**
 * For implementation details of the content stream states and removed state, see {@see ContentStream}.
 *
 * @api
 */
class ContentStreamPruner implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly EventStoreInterface $eventStore,
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
     * By default, only content streams in STATE_NO_LONGER_IN_USE and STATE_REBASE_ERROR will be removed.
     * If you also call with $removeTemporary=true, will delete ALL content streams which are currently not assigned
     * to a workspace (f.e. dangling ones in FORKED or CREATED.).
     *
     * @param bool $removeTemporary if TRUE, will delete ALL content streams not bound to a workspace
     * @return iterable<int,ContentStreamId> the identifiers of the removed content streams
     */
    public function prune(bool $removeTemporary = false): iterable
    {
        $status = [ContentStreamStatus::NO_LONGER_IN_USE, ContentStreamStatus::REBASE_ERROR];
        if ($removeTemporary) {
            $status[] = ContentStreamStatus::CREATED;
            $status[] = ContentStreamStatus::FORKED;
        }
        $unusedContentStreams = $this->contentRepository->findContentStreams()->filter(
            static fn (ContentStream $contentStream) => in_array($contentStream->status, $status, true),
        );
        $unusedContentStreamIds = [];
        foreach ($unusedContentStreams as $contentStream) {
            $this->contentRepository->handle(
                RemoveContentStream::create($contentStream->id)
            );
            $unusedContentStreamIds[] = $contentStream->id;
        }

        return $unusedContentStreamIds;
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
     * @return ContentStreams the removed content streams
     */
    public function pruneRemovedFromEventStream(): ContentStreams
    {
        $removedContentStreams = $this->findUnusedAndRemovedContentStreams();
        foreach ($removedContentStreams as $removedContentStream) {
            $streamName = ContentStreamEventStreamName::fromContentStreamId($removedContentStream->id)
                ->getEventStreamName();
            $this->eventStore->deleteStream($streamName);
        }
        return $removedContentStreams;
    }

    public function pruneAll(): void
    {
        foreach ($this->contentRepository->findContentStreams() as $contentStream) {
            $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStream->id)->getEventStreamName();
            $this->eventStore->deleteStream($streamName);
        }
    }

    private function findUnusedAndRemovedContentStreams(): ContentStreams
    {
        $allContentStreams = $this->contentRepository->findContentStreams();

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
                $removedContentStreams[] = $contentStream;
            }
        }

        return ContentStreams::fromArray($removedContentStreams);
    }
}
