<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamAlreadyExists;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal
 */
trait ContentStreamHandling
{
    /**
     * @param ContentStreamId $newContentStreamId The id of the new content stream
     * @param ContentStreamId $sourceContentStreamId The id of the content stream to fork
     * @phpstan-pure this method is pure, to persist the events they must be handled outside
     */
    private function forkContentStream(
        ContentStreamId $newContentStreamId,
        ContentStreamId $sourceContentStreamId,
        Version $sourceContentStreamVersion,
        string $debugReason
    ): EventsToPublish {
        return EventsToPublish::createEventsForStreamAndExpectedVersion(
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
    private function removeContentStream(
        ContentStreamId $contentStreamId,
        Version $contentStreamVersion,
    ): EventsToPublish {
        return EventsToPublish::createEventsForStreamAndExpectedVersion(
            ContentStreamEventStreamName::fromContentStreamId($contentStreamId)->getEventStreamName(),
            Events::with(
                new ContentStreamWasRemoved(
                    $contentStreamId,
                ),
            ),
            ExpectedVersion::fromVersion($contentStreamVersion)
        );
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @throws ContentStreamAlreadyExists
     */
    private function requireContentStreamToNotExistYet(
        ContentStreamId $contentStreamId,
    ): void {
        if ($this->commandHandlingDependencies->contentStreamExists($contentStreamId)) {
            throw new ContentStreamAlreadyExists(
                'Content stream "' . $contentStreamId->value . '" already exists.',
                1521386345
            );
        }
    }
}
