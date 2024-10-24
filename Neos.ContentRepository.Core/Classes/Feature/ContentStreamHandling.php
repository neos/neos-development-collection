<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasReopened;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamAlreadyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamIsClosed;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamIsNotClosed;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

trait ContentStreamHandling
{
    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to create
     * @throws ContentStreamAlreadyExists
     */
    private function createContentStream(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        $this->requireContentStreamToNotExistYet($contentStreamId, $commandHandlingDependencies);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStreamId)
            ->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasCreated(
                    $contentStreamId,
                )
            ),
            ExpectedVersion::NO_STREAM()
        );
    }

    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to close
     * @param CommandHandlingDependencies $commandHandlingDependencies
     * @return EventsToPublish
     */
    private function closeContentStream(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        $this->requireContentStreamToExist($contentStreamId, $commandHandlingDependencies);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentStreamId, $commandHandlingDependencies);
        $this->requireContentStreamToNotBeClosed($contentStreamId, $commandHandlingDependencies);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStreamId)->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasClosed(
                    $contentStreamId,
                ),
            ),
            $expectedVersion
        );
    }

    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to reopen
     * @param ContentStreamStatus $previousState The state the content stream was in before closing and is to be reset to
     */
    private function reopenContentStream(
        ContentStreamId $contentStreamId,
        ContentStreamStatus $previousState,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        $this->requireContentStreamToExist($contentStreamId, $commandHandlingDependencies);
        $this->requireContentStreamToBeClosed($contentStreamId, $commandHandlingDependencies);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStreamId)->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasReopened(
                    $contentStreamId,
                    $previousState,
                ),
            ),
            ExpectedVersion::ANY()
        );
    }

    /**
     * @param ContentStreamId $newContentStreamId The id of the new content stream
     * @param ContentStreamId $sourceContentStreamId The id of the content stream to fork
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     */
    private function forkContentStream(
        ContentStreamId $newContentStreamId,
        ContentStreamId $sourceContentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $this->requireContentStreamToExist($sourceContentStreamId, $commandHandlingDependencies);
        $this->requireContentStreamToNotBeClosed($sourceContentStreamId, $commandHandlingDependencies);
        $this->requireContentStreamToNotExistYet($newContentStreamId, $commandHandlingDependencies);

        $sourceContentStreamVersion = $commandHandlingDependencies->getContentStreamVersion($sourceContentStreamId);

        $streamName = ContentStreamEventStreamName::fromContentStreamId($newContentStreamId)
            ->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasForked(
                    $newContentStreamId,
                    $sourceContentStreamId,
                    $sourceContentStreamVersion,
                ),
            ),
            // NO_STREAM to ensure the "fork" happens as the first event of the new content stream
            ExpectedVersion::NO_STREAM()
        );
    }

    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to remove
     */
    private function removeContentStream(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $this->requireContentStreamToExist($contentStreamId, $commandHandlingDependencies);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentStreamId, $commandHandlingDependencies);

        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            $contentStreamId
        )->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasRemoved(
                    $contentStreamId,
                ),
            ),
            $expectedVersion
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

    /**
     * @param ContentStreamId $contentStreamId
     * @param CommandHandlingDependencies $commandHandlingDependencies
     * @throws ContentStreamDoesNotExistYet
     */
    private function requireContentStreamToExist(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): void {
        if (!$commandHandlingDependencies->contentStreamExists($contentStreamId)) {
            throw new ContentStreamDoesNotExistYet(
                'Content stream "' . $contentStreamId->value . '" does not exist yet.',
                1521386692
            );
        }
    }

    private function requireContentStreamToNotBeClosed(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): void {
        if ($commandHandlingDependencies->getContentStreamStatus($contentStreamId) === ContentStreamStatus::CLOSED) {
            throw new ContentStreamIsClosed(
                'Content stream "' . $contentStreamId->value . '" is closed.',
                1710260081
            );
        }
    }

    private function requireContentStreamToBeClosed(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): void {
        if ($commandHandlingDependencies->getContentStreamStatus($contentStreamId) !== ContentStreamStatus::CLOSED) {
            throw new ContentStreamIsNotClosed(
                'Content stream "' . $contentStreamId->value . '" is not closed.',
                1710405911
            );
        }
    }

    private function getExpectedVersionOfContentStream(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): ExpectedVersion {
        $version = $commandHandlingDependencies->getContentStreamVersion($contentStreamId);
        return ExpectedVersion::fromVersion($version);
    }
}
