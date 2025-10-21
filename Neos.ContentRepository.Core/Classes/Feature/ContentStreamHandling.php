<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\CommandHandler\CommandHandlingDependencies;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasReopened;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamAlreadyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamIsClosed;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal
 */
trait ContentStreamHandling
{
    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to close
     * @phpstan-pure this method is pure, to persist the events they must be handled outside
     */
    private function closeContentStream(
        ContentStreamId $contentStreamId,
        Version $contentStreamVersion,
    ): EventsToPublish {
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStreamId)->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasClosed(
                    $contentStreamId,
                ),
            ),
            ExpectedVersion::fromVersion($contentStreamVersion)
        );
    }

    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to reopen
     * @phpstan-pure this method is pure, to persist the events they must be handled outside
     */
    private function reopenContentStreamWithoutConstraintChecks(
        ContentStreamId $contentStreamId,
        string $debugReason
    ): EventsToPublish {
        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentStreamId)->getEventStreamName(),
            Events::with(
                DecoratedEvent::create(
                    new ContentStreamWasReopened(
                        $contentStreamId
                    ),
                    metadata: array_filter(['debug_reason' => $debugReason])
                )
            ),
            // We operate here without constraints on purpose to ensure this can be commited.
            // Constraints have been checked beforehand and its expected that the content stream is closed.
            ExpectedVersion::ANY()
        );
    }

    /**
     * @param ContentStreamId $newContentStreamId The id of the new content stream
     * @param ContentStreamId $sourceContentStreamId The id of the content stream to fork
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     * @phpstan-pure this method is pure, to persist the events they must be handled outside
     */
    private function forkContentStream(
        ContentStreamId $newContentStreamId,
        ContentStreamId $sourceContentStreamId,
        Version $sourceContentStreamVersion,
        string $debugReason
    ): EventsToPublish {
        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($newContentStreamId)->getEventStreamName(),
            Events::with(
                DecoratedEvent::create(
                    event: new ContentStreamWasForked(
                        $newContentStreamId,
                        $sourceContentStreamId,
                        $sourceContentStreamVersion,
                    ),
                    metadata: ['debug_reason' => $debugReason]
                )
            ),
            // NO_STREAM to ensure the "fork" happens as the first event of the new content stream
            ExpectedVersion::NO_STREAM()
        );
    }

    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to remove
     * @phpstan-pure this method is pure, to persist the events they must be handled outside
     */
    private function removeContentStreamWithoutConstraintChecks(
        ContentStreamId $contentStreamId,
    ): EventsToPublish {
        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentStreamId)->getEventStreamName(),
            Events::with(
                new ContentStreamWasRemoved(
                    $contentStreamId,
                ),
            ),
            // We operate here without constraints on purpose to ensure this can be commited.
            // Constraints have been checked beforehand and its expected that the content stream is closed.
            ExpectedVersion::ANY()
        );
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param CommandHandlingDependencies $commandHandlingDependencies
     * @throws ContentStreamAlreadyExists
     */
    private function requireContentStreamToNotExistYet(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): void {
        if ($commandHandlingDependencies->contentStreamExists($contentStreamId)) {
            throw new ContentStreamAlreadyExists(
                'Content stream "' . $contentStreamId->value . '" already exists.',
                1521386345
            );
        }
    }

    private function requireContentStreamToNotBeClosed(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): void {
        if ($commandHandlingDependencies->isContentStreamClosed($contentStreamId)) {
            throw new ContentStreamIsClosed(
                'Content stream "' . $contentStreamId->value . '" is closed.',
                1710260081
            );
        }
    }
}
