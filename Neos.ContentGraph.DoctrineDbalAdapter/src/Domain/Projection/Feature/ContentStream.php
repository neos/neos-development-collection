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
    private function createContentStream(ContentStreamId $contentStreamId): void
    {
        $this->dbal->insert($this->tableNames->contentStream(), [
            'id' => $contentStreamId->value,
            'version' => 0,
            'publishableEvents' => 0,
            'sourceContentStreamId' => null,
            'sourceContentStreamVersion' => null,
            'sourcePublishableEvents' => null,
        ]);
    }

    private function createForkedContentStream(ContentStreamId $contentStreamId, ContentStreamId $sourceContentStreamId, Version $sourceVersion): void
    {
        $this->dbal->executeStatement(
            <<<SQL
            INSERT INTO {$this->tableNames->contentStream()} (
              id,
              version,
              sourceContentStreamId,
              sourceContentStreamVersion,
              publishableEvents,
              sourcePublishableEvents
            )
            SELECT
              :id,
              0 as version,
              :sourceContentStreamId,
              :sourceContentStreamVersion,
              0 as publishableEvents,
              c.publishableEvents as sourcePublishableEvents
            FROM {$this->tableNames->contentStream()} c
              WHERE c.id = :sourceContentStreamId
            SQL,
            [
                'id' => $contentStreamId->value,
                'sourceContentStreamId' => $sourceContentStreamId->value,
                'sourceContentStreamVersion' => $sourceVersion->value,
            ]
        );
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
