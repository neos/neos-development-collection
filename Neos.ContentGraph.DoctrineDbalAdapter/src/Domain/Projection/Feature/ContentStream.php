<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\Version;

/**
 * The ContentStream projection feature trait
 *
 * @internal
 */
trait ContentStream
{
    private function createContentStream(ContentStreamId $contentStreamId, ?ContentStreamId $sourceContentStreamId = null, ?Version $sourceVersion = null): void
    {
        $this->dbal->insert($this->tableNames->contentStream(), [
            'id' => $contentStreamId->value,
            'version' => 0,
            'sourceContentStreamId' => $sourceContentStreamId?->value,
            'sourceContentStreamVersion' => $sourceVersion?->value,
            'publishableEvents' => 0
        ]);
    }

    private function removeContentStream(ContentStreamId $contentStreamId): void
    {
        $this->dbal->delete($this->tableNames->contentStream(), [
            'id' => $contentStreamId->value
        ]);
    }

    private function updateContentStreamVersion(ContentStreamId $contentStreamId, Version $version, bool $isPublishableEvent): void
    {
        if ($isPublishableEvent) {
            $this->dbal->executeStatement(
                "UPDATE {$this->tableNames->contentStream()} SET version=:version, publishableEvents=publishableEvents+1 WHERE id=:id",
                [
                    'version' => $version->value,
                    'id' => $contentStreamId->value,
                ]
            );
        } else {
            $this->dbal->update($this->tableNames->contentStream(), [
                'version' => $version->value,
            ], [
                'id' => $contentStreamId->value,
            ]);
        }
    }
}
