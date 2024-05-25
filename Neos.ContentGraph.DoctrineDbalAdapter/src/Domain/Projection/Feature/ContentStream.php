<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamState;
use Neos\EventStore\Model\Event\Version;

/**
 * The ContentStream projection feature trait
 *
 * @internal
 */
trait ContentStream
{
    private function createContentStream(ContentStreamId $contentStreamId, ContentStreamState $state, ?ContentStreamId $sourceContentStreamId = null): void
    {
        $this->dbal->insert($this->tableNames->contentStream(), [
            'contentStreamId' => $contentStreamId->value,
            'sourceContentStreamId' => $sourceContentStreamId?->value,
            'version' => 0,
            'state' => $state->value,
        ]);
    }

    private function updateContentStreamState(ContentStreamId $contentStreamId, ContentStreamState $state): void
    {
        $this->dbal->update($this->tableNames->contentStream(), [
            'state' => $state->value,
        ], [
            'contentStreamId' => $contentStreamId->value
        ]);
    }

    private function removeContentStream(ContentStreamId $contentStreamId): void
    {
        $this->dbal->update($this->tableNames->contentStream(), [
            'removed' => true,
        ], [
            'contentStreamId' => $contentStreamId->value
        ]);
    }

    private function updateContentStreamVersion(ContentStreamId $contentStreamId, Version $version): void
    {
        $this->dbal->update($this->tableNames->contentStream(), [
            'version' => $version->value,
        ], [
            'contentStreamId' => $contentStreamId->value,
        ]);
    }
}
