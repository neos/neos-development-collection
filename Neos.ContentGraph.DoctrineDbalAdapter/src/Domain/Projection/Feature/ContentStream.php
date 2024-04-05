<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamState;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventEnvelope;

/**
 * The ContentStream projection feature trait
 *
 * @internal
 */
trait ContentStream
{
    abstract protected function getProjectionContentGraph(): ProjectionContentGraph;

    abstract protected function getTableNamePrefix(): string;

    abstract protected function getDatabaseConnection(): Connection;



    private function addContentStream(ContentStreamId $contentStreamId, Version $version): void
    {
        $this->getDatabaseConnection()->insert($this->getTableNamePrefix() . '_contentstream', [
            'contentStreamId' => $contentStreamId->value,
            'version' => $version->value,
            'state' => ContentStreamState::STATE_CREATED->value
        ]);
    }

    private function markContentStreamForked(ContentStreamId $contentStreamId, Version $version, ContentStreamId $sourceContentStreamId): void
    {
        $this->getDatabaseConnection()->insert($this->getTableNamePrefix() . '_contentstream', [
            'contentStreamId' => $contentStreamId->value,
            'version' => $version->value,
            'sourceContentStreamId' => $sourceContentStreamId->value,
            'state' => ContentStreamState::STATE_FORKED->value
        ]);
    }

    private function setContentStreamState(ContentStreamId $contentStreamId, ContentStreamState $newState): void
    {
        $this->getDatabaseConnection()->update($this->getTableNamePrefix() . '_contentstream', [
            'state' => $newState->value,
        ], [
            'contentStreamId' => $contentStreamId->value
        ]);
    }
    private function setContentStreamVersion(ContentStreamId $contentStreamId, Version $newVersion): void
    {
        $this->getDatabaseConnection()->update($this->getTableNamePrefix() . '_contentstream', [
            'version' => $newVersion->value,
        ], [
            'contentStreamId' => $contentStreamId->value
        ]);
    }

    private function markContentStreamRemoved(ContentStreamId $contentStreamId, Version $version): void
    {
        $this->getDatabaseConnection()->update($this->getTableNamePrefix() . '_contentstream', [
            'removed' => true,
            'version' => $version->value,
        ], [
            'contentStreamId' => $contentStreamId->value
        ]);
    }

}
