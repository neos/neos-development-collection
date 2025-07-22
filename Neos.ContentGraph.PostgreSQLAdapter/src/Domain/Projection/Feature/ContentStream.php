<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasReopened;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\Version;

/**
 * The ContentStream projection feature trait
 *
 * @internal
 */
trait ContentStream
{
    // ### ----------- event dispatchers

    private function whenContentStreamWasClosed(ContentStreamWasClosed $event): void
    {
        $this->closeContentStream($event->contentStreamId);
    }

    private function whenContentStreamWasCreated(ContentStreamWasCreated $event): void
    {
        $this->createContentStream($event->contentStreamId);
    }

    private function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        $this->removeContentStream($event->contentStreamId);
    }

    private function whenContentStreamWasReopened(ContentStreamWasReopened $event): void
    {
        $this->reopenContentStream($event->contentStreamId);
    }

    // ### ----------- internal API

    private function createContentStream(ContentStreamId $contentStreamId, ?ContentStreamId $sourceContentStreamId = null, ?Version $sourceVersion = null): void
    {
        $this->dbal->insert($this->tableNames->contentStream(), [
            'id' => $contentStreamId->value,
            'version' => 0,
            'sourcecontentstreamid' => $sourceContentStreamId?->value,
            'sourcecontentstreamversion' => $sourceVersion?->value,
            'isclosed' => 0,
            'haschanges' => 0
        ]);
    }

    private function closeContentStream(ContentStreamId $contentStreamId): void
    {
        $this->getDatabaseConnection()->update($this->getTableNames()->contentStream(), [
            'isclosed' => 1,
        ], [
            'id' => $contentStreamId->value
        ]);
    }

    private function reopenContentStream(ContentStreamId $contentStreamId): void
    {
        $this->getDatabaseConnection()->update($this->getTableNames()->contentStream(), [
            'isclosed' => 0,
        ], [
            'id' => $contentStreamId->value
        ]);
    }

    private function removeContentStream(ContentStreamId $contentStreamId): void
    {
        $this->getDatabaseConnection()->delete($this->getTableNames()->contentStream(), [
            'id' => $contentStreamId->value
        ]);
    }

    private function updateContentStreamVersion(ContentStreamId $contentStreamId, Version $version, bool $markAsDirty): void
    {
        $updatePayload = [
            'version' => $version->value,
        ];
        if ($markAsDirty) {
            $updatePayload['haschanges'] = 1;
        }
        $this->getDatabaseConnection()->update($this->getTableNames()->contentStream(), $updatePayload, [
            'id' => $contentStreamId->value,
        ]);
    }

    protected abstract function getDatabaseConnection(): Connection;

    protected abstract function getTableNames(): ContentGraphTableNames;

}
