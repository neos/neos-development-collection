<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\ContentRepositoryReadModel;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Command\RemoveContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;
use Neos\EventStore\EventStoreInterface;

/**
 * For internal implementation details, see {@see ContentRepositoryReadModel}.
 *
 * @api
 */
class ContentStreamPruner implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly EventStoreInterface $eventStore,
        private readonly ContentRepositoryReadModel $contentRepositoryReadModel,
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
     * @return iterable<ContentStreamId> the identifiers of the removed content streams
     */
    public function pruneRemovedFromEventStream(): iterable
    {
        $removedContentStreamIds = $this->contentRepositoryReadModel->findUnusedAndRemovedContentStreamIds();
        foreach ($removedContentStreamIds as $removedContentStream) {
            $streamName = ContentStreamEventStreamName::fromContentStreamId($removedContentStream)
                ->getEventStreamName();
            $this->eventStore->deleteStream($streamName);
        }
        return $removedContentStreamIds;
    }

    public function pruneAll(): void
    {
        foreach ($this->contentRepository->findContentStreams() as $contentStream) {
            $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStream->id)->getEventStreamName();
            $this->eventStore->deleteStream($streamName);
        }
    }
}
