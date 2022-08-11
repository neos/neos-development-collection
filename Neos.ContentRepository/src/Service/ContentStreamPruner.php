<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Service;

use Neos\ContentRepository\CommandHandler\CommandResult;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\ContentStreamRemoval\Command\RemoveContentStream;
use Neos\EventStore\EventStoreInterface;

/**
 * @api
 */
class ContentStreamPruner implements ContentRepositoryServiceInterface
{
    private ?CommandResult $lastCommandResult;

    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly EventStoreInterface $eventStore
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
     * @return iterable<int,ContentStreamIdentifier> the identifiers of the removed content streams
     */
    public function prune(): iterable
    {
        $unusedContentStreams = $this->contentRepository->getContentStreamFinder()->findUnusedContentStreams();

        foreach ($unusedContentStreams as $contentStream) {
            $this->lastCommandResult = $this->contentRepository->handle(
                new RemoveContentStream(
                    $contentStream,
                    UserIdentifier::forSystemUser()
                )
            );
        }

        return $unusedContentStreams;
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
     * @return iterable<int,ContentStreamIdentifier> the identifiers of the removed content streams
     */
    public function pruneRemovedFromEventStream(): iterable
    {
        $removedContentStreams = $this->contentRepository->getContentStreamFinder()
            ->findUnusedAndRemovedContentStreams();

        foreach ($removedContentStreams as $removedContentStream) {
            $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($removedContentStream)
                ->getEventStreamName();
            $this->eventStore->deleteStream($streamName);
        }

        return $removedContentStreams;
    }

    public function getLastCommandResult(): ?CommandResult
    {
        return $this->lastCommandResult;
    }
}
