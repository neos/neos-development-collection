<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;
use Neos\EventStore\Model\Event\Version;

/**
 * The ContentStream projection feature trait
 *
 * @internal
 */
trait ContentStream
{
    private function createContentStream(ContentStreamId $contentStreamId, ContentStreamStatus $status, ?ContentStreamId $sourceContentStreamId = null, ?Version $sourceVersion = null): void
    {
        $this->dbal->insert($this->tableNames->contentStream(), [
            'id' => $contentStreamId->value,
            'version' => 0,
            'sourceContentStreamId' => $sourceContentStreamId?->value,
            'sourceContentStreamVersion' => $sourceVersion?->value,
            'status' => $status->value,
        ]);
    }

    private function updateContentStreamStatus(ContentStreamId $contentStreamId, ContentStreamStatus $status): void
    {
        $this->dbal->update($this->tableNames->contentStream(), [
            'status' => $status->value,
        ], [
            'id' => $contentStreamId->value
        ]);
    }

    private function removeContentStream(ContentStreamId $contentStreamId): void
    {
        $this->dbal->update($this->tableNames->contentStream(), [
            'removed' => true,
        ], [
            'id' => $contentStreamId->value
        ]);
    }

    private function updateContentStreamVersion(ContentStreamId $contentStreamId, Version $version): void
    {
        $this->dbal->update($this->tableNames->contentStream(), [
            'version' => $version->value,
        ], [
            'id' => $contentStreamId->value,
        ]);
    }
}
