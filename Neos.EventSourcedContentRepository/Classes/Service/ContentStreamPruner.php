<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Service;

use Doctrine\DBAL\Connection;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Command\RemoveContentStream;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\ContentStream\ContentStreamFinder;

#[Flow\Scope("singleton")]
class ContentStreamPruner
{
    protected ContentStreamFinder $contentStreamFinder;

    protected ContentStreamCommandHandler $contentStreamCommandHandler;

    protected Connection $connection;

    protected ?CommandResult $lastCommandResult;

    public function __construct(
        ContentStreamFinder $contentStreamFinder,
        ContentStreamCommandHandler $contentStreamCommandHandler,
        DbalClient $dbalClient
    ) {
        $this->contentStreamFinder = $contentStreamFinder;
        $this->contentStreamCommandHandler = $contentStreamCommandHandler;
        $this->connection = $dbalClient->getConnection();
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
     * @return ContentStreamIdentifier[] the removed content streams
     */
    public function prune(): array
    {
        $unusedContentStreams = $this->contentStreamFinder->findUnusedContentStreams();

        foreach ($unusedContentStreams as $contentStream) {
            $this->lastCommandResult = $this->contentStreamCommandHandler->handleRemoveContentStream(
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
     * @return ContentStreamIdentifier[] the removed content streams
     */
    public function pruneRemovedFromEventStream(): array
    {
        $removedContentStreams = $this->contentStreamFinder->findUnusedAndRemovedContentStreams();

        foreach ($removedContentStreams as $removedContentStream) {
            $this->connection->executeUpdate(
                'DELETE FROM neos_contentrepository_events WHERE stream = :stream',
                [
                    'stream' => (string)ContentStreamEventStreamName::fromContentStreamIdentifier($removedContentStream)
                        ->getEventStreamName()
                ]
            );
        }

        return $removedContentStreams;
    }

    /**
     * @return mixed
     */
    public function getLastCommandResult(): ?CommandResult
    {
        return $this->lastCommandResult;
    }
}
