<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\InitiatingEventMetadata;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasReopened;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateDimensionsWereUpdated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceBaseWorkspaceWasChanged;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\Feature\Workspaces;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryContentGraphStructure;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryHierarchyHyperrelationRecord;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryHierarchyHyperrelationRecordSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryNodeRecord;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryNodeRecords;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository\InMemoryContentStreamRegistry;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository\InMemoryWorkspaceRegistry;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @internal but the graph projection is api
 */
final class InMemoryContentGraphProjection implements ContentGraphProjectionInterface
{
    use Workspaces;

    public function __construct(
        private readonly ContentGraphReadModelInterface $contentGraphReadModel,
        private InMemoryContentGraphStructure $graphStructure,
        private readonly InMemoryWorkspaceRegistry $workspaceRegistry,
        private readonly InMemoryContentStreamRegistry $contentStreamRegistry,
    ) {
    }

    public function setUp(): void
    {
        // nothing to do here
    }

    public function status(): ProjectionStatus
    {
        return ProjectionStatus::ok();
    }

    public function resetState(): void
    {
        $this->contentStreamRegistry->reset();
        $this->workspaceRegistry->reset();
        $this->graphStructure->reset();
    }

    public function getState(): ContentGraphReadModelInterface
    {
        return $this->contentGraphReadModel;
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        match ($event::class) {
            ContentStreamWasClosed::class => $this->whenContentStreamWasClosed($event),
            ContentStreamWasCreated::class => $this->whenContentStreamWasCreated($event),
            ContentStreamWasForked::class => $this->whenContentStreamWasForked($event),
            ContentStreamWasRemoved::class => $this->whenContentStreamWasRemoved($event),
            ContentStreamWasReopened::class => $this->whenContentStreamWasReopened($event),
            DimensionShineThroughWasAdded::class => $this->whenDimensionShineThroughWasAdded($event),
            DimensionSpacePointWasMoved::class => $this->whenDimensionSpacePointWasMoved($event),
            NodeAggregateNameWasChanged::class => $this->whenNodeAggregateNameWasChanged($event, $eventEnvelope),
            NodeAggregateTypeWasChanged::class => $this->whenNodeAggregateTypeWasChanged($event, $eventEnvelope),
            NodeAggregateWasMoved::class => $this->whenNodeAggregateWasMoved($event),
            NodeAggregateWasRemoved::class => $this->whenNodeAggregateWasRemoved($event),
            NodeAggregateWithNodeWasCreated::class => $this->whenNodeAggregateWithNodeWasCreated($event, $eventEnvelope),
            NodeGeneralizationVariantWasCreated::class => $this->whenNodeGeneralizationVariantWasCreated($event, $eventEnvelope),
            NodePeerVariantWasCreated::class => $this->whenNodePeerVariantWasCreated($event, $eventEnvelope),
            NodePropertiesWereSet::class => $this->whenNodePropertiesWereSet($event, $eventEnvelope),
            NodeReferencesWereSet::class => $this->whenNodeReferencesWereSet($event, $eventEnvelope),
            NodeSpecializationVariantWasCreated::class => $this->whenNodeSpecializationVariantWasCreated($event, $eventEnvelope),
            RootNodeAggregateDimensionsWereUpdated::class => $this->whenRootNodeAggregateDimensionsWereUpdated($event),
            RootNodeAggregateWithNodeWasCreated::class => $this->whenRootNodeAggregateWithNodeWasCreated($event, $eventEnvelope),
            RootWorkspaceWasCreated::class => $this->whenRootWorkspaceWasCreated($event),
            SubtreeWasTagged::class => $this->whenSubtreeWasTagged($event),
            SubtreeWasUntagged::class => $this->whenSubtreeWasUntagged($event),
            WorkspaceBaseWorkspaceWasChanged::class => $this->whenWorkspaceBaseWorkspaceWasChanged($event),
            WorkspaceRebaseFailed::class => $this->whenWorkspaceRebaseFailed($event),
            WorkspaceWasCreated::class => $this->whenWorkspaceWasCreated($event),
            WorkspaceWasDiscarded::class => $this->whenWorkspaceWasDiscarded($event),
            WorkspaceWasPublished::class => $this->whenWorkspaceWasPublished($event),
            WorkspaceWasRebased::class => $this->whenWorkspaceWasRebased($event),
            WorkspaceWasRemoved::class => $this->whenWorkspaceWasRemoved($event),
            default => null,
        };
        if (
            $event instanceof EmbedsContentStreamId
            && ContentStreamEventStreamName::isContentStreamStreamName($eventEnvelope->streamName)
            && !(
                // special case as we don't need to update anything. The handling above takes care of setting the version to 0
                $event instanceof ContentStreamWasForked
                || $event instanceof ContentStreamWasCreated
            )
        ) {
            $this->contentStreamRegistry->updateContentStreamVersion($event->getContentStreamId(), $eventEnvelope->version, $event instanceof PublishableToWorkspaceInterface);
        }
    }

    public function inSimulation(\Closure $fn): mixed
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo just copy stuff, apply stuff and roll back or keep stuff */
    }

    private function whenContentStreamWasClosed(ContentStreamWasClosed $event): void
    {
        $this->contentStreamRegistry->closeContentStream($event->contentStreamId);
    }

    private function whenContentStreamWasCreated(ContentStreamWasCreated $event): void
    {
        $this->contentStreamRegistry->createContentStream($event->contentStreamId);
    }

    private function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo copy hierarchy relations if they are implemented */
        #$this->contentStreamRegistry->createContentStream($event->newContentStreamId, $event->sourceContentStreamId, $event->versionOfSourceContentStream);
    }

    private function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo remove hierarchy relations if they are implemented */
        #$this->contentStreamRegistry->removeContentStream($event->contentStreamId);
    }

    private function whenContentStreamWasReopened(ContentStreamWasReopened $event): void
    {
        $this->contentStreamRegistry->reopenContentStream($event->contentStreamId);
    }

    private function whenDimensionShineThroughWasAdded(DimensionShineThroughWasAdded $event): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo add hierarchy relations if they are implemented */
    }

    private function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        // the ordering is important - we first update the OriginDimensionSpacePoints, as we need the
        // hierarchy relations for this query. Then, we update the Hierarchy Relations.

        // 1) originDimensionSpacePoint on Node
        /** @todo adjust nodes with copy on write */

        // 2) hierarchy relations
        /** @todo adjust hierarchy relations */
    }

    private function whenNodeAggregateNameWasChanged(NodeAggregateNameWasChanged $event, EventEnvelope $eventEnvelope): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo adjust nodes with copy on write */
    }

    private function whenNodeAggregateTypeWasChanged(NodeAggregateTypeWasChanged $event, EventEnvelope $eventEnvelope): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo adjust nodes with copy on write */
    }

    private function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo restructure in-memory graph structure */
    }

    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo recursively remove stuff */
    }

    private function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $origin = OriginDimensionSpacePoint::createWithoutDimensions();
        $nodeRecord = new InMemoryNodeRecord(
            $event->nodeAggregateId,
            $origin,
            SerializedPropertyValues::createEmpty(),
            $event->nodeTypeName,
            $event->nodeAggregateClassification,
            null,
            Timestamps::create($eventEnvelope->recordedAt, self::resolveInitiatingDateTime($eventEnvelope), null, null),
            [],
            [],
            SubtreeTags::createEmpty(),
            SubtreeTags::createEmpty(),
        );
        $parentRelation = new InMemoryHierarchyHyperrelationRecordSet();
        foreach ($event->coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $parentRelation->attach(
                new InMemoryHierarchyHyperrelationRecord(
                    null, $coveredDimensionSpacePoint, InMemoryNodeRecords::create($nodeRecord)
                ),
            );
        }
        $nodeRecord->parentsByContentStreamId[$event->contentStreamId->value] = $parentRelation;
        $nodeRecord->childrenByContentStream[$event->contentStreamId->value] = new InMemoryHierarchyHyperrelationRecordSet();

        $this->graphStructure->rootNodes[$event->contentStreamId->value][$event->nodeTypeName->value] = $nodeRecord;
        $this->graphStructure->addNodeRecord($nodeRecord, $event->contentStreamId);
    }

    private function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $parentRelations = new InMemoryHierarchyHyperrelationRecordSet();
        $parentNodeRecords = $this->graphStructure->nodes[$event->contentStreamId->value][$event->parentNodeAggregateId->value] ?? null;
        if ($parentNodeRecords === null) {
            throw new \RuntimeException('Failed to resolve parent node records', 1745180042);
        }

        $nodeRecord = new InMemoryNodeRecord(
            $event->nodeAggregateId,
            $event->originDimensionSpacePoint,
            $event->initialPropertyValues,
            $event->nodeTypeName,
            $event->nodeAggregateClassification,
            $event->nodeName,
            Timestamps::create($eventEnvelope->recordedAt, self::resolveInitiatingDateTime($eventEnvelope), null, null),
            [
                $event->contentStreamId->value => $parentRelations,
            ],
            [
                $event->contentStreamId->value => new InMemoryHierarchyHyperrelationRecordSet(),
            ],
            SubtreeTags::createEmpty(),
            SubtreeTags::createEmpty(),
        );

        foreach ($parentNodeRecords as $parentNodeRecord) {
            $relevantDimensionSpacePoints = $parentNodeRecord->getCoveredDimensionSpacePointSet($event->contentStreamId)
                ->getIntersection($event->succeedingSiblingsForCoverage->toDimensionSpacePointSet());
            foreach ($relevantDimensionSpacePoints as $relevantDimensionSpacePoint) {
                $parentRelation = $parentNodeRecord->childrenByContentStream[$event->contentStreamId->value]->getHierarchyHyperrelation($relevantDimensionSpacePoint);
                if ($parentRelation === null) {
                    $parentRelation = new InMemoryHierarchyHyperrelationRecord(
                        $parentNodeRecord,
                        $relevantDimensionSpacePoint,
                        InMemoryNodeRecords::create($nodeRecord),
                    );
                    $parentNodeRecord->childrenByContentStream[$event->contentStreamId->value]->attach($parentRelation);
                } else {
                    $parentRelation->children->insert($nodeRecord, $event->succeedingSiblingsForCoverage->getSucceedingSiblingIdForDimensionSpacePoint($relevantDimensionSpacePoint));
                }
                $parentRelations->attach($parentRelation);
            }
        }

        $this->graphStructure->addNodeRecord($nodeRecord, $event->contentStreamId);
    }

    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $originRecord = $this->graphStructure->nodes[$event->contentStreamId->value][$event->nodeAggregateId->value][$event->sourceOrigin->hash] ?? null;
        if ($originRecord === null) {
            throw new \RuntimeException('Failed to resolve origin node record', 1745229966);
        }
        $originParentRecord = $originRecord->parentsByContentStreamId[$event->contentStreamId->value]
            ->getHierarchyHyperrelation($originRecord->originDimensionSpacePoint->toDimensionSpacePoint())
            ->parent;
        if ($originParentRecord === null) {
            throw new \RuntimeException('Failed to resolve parent node record of origin', 1745361216);
        }
        $parentNodeRecords = $this->graphStructure->nodes[$event->contentStreamId->value][$originParentRecord->nodeAggregateId->value] ?? null;
        if ($parentNodeRecords === null) {
            throw new \RuntimeException('Failed to resolve parent node records', 1745180042);
        }
        $parentRelations = new InMemoryHierarchyHyperrelationRecordSet();

        $aggregateNodeRecords = $this->graphStructure->nodes[$event->contentStreamId->value][$event->nodeAggregateId->value];
        $childRelations = new InMemoryHierarchyHyperrelationRecordSet();

        $specializationRecord = new InMemoryNodeRecord(
            $event->nodeAggregateId,
            $event->specializationOrigin,
            $originRecord->properties,
            $originRecord->nodeTypeName,
            $originRecord->classification,
            $originRecord->name,
            Timestamps::create($eventEnvelope->recordedAt, self::resolveInitiatingDateTime($eventEnvelope), null, null),
            [
                $event->contentStreamId->value => $parentRelations,
            ],
            [
                $event->contentStreamId->value => $childRelations,
            ],
            SubtreeTags::createEmpty(),
            SubtreeTags::createEmpty(),
        );

        foreach ($event->specializationSiblings as $specializationSibling) {
            // reassign child relations of the specialization's new parents
            foreach ($parentNodeRecords as $parentNodeRecord) {
                if ($parentNodeRecord->coversDimensionSpacePoint($event->contentStreamId, $specializationSibling->dimensionSpacePoint)) {
                    $specializationParentRelation = $parentNodeRecord->childrenByContentStream[$event->contentStreamId->value]
                        ->getHierarchyHyperrelation($specializationSibling->dimensionSpacePoint);
                    if ($specializationParentRelation) {
                        $specializationParentRelation->children->removeIfContained($originRecord);
                        $specializationParentRelation->children->insert($specializationRecord, $specializationSibling->nodeAggregateId);
                        $originRecord->parentsByContentStreamId[$event->contentStreamId->value]->detach($specializationParentRelation);
                    } else {
                        $specializationParentRelation = new InMemoryHierarchyHyperrelationRecord(
                            $parentNodeRecord,
                            $specializationSibling->dimensionSpacePoint,
                            InMemoryNodeRecords::create($specializationRecord)
                        );
                        $parentNodeRecord->childrenByContentStream[$event->contentStreamId->value]->attach($specializationParentRelation);
                    }
                    $parentRelations->attach($specializationParentRelation);
                }
            }

            // reassign parent relations of the specialization's new children
            foreach ($aggregateNodeRecords as $aggregateNodeRecord) {
                if ($aggregateNodeRecord->coversDimensionSpacePoint($event->contentStreamId, $specializationSibling->dimensionSpacePoint)) {
                    $childRelation = $aggregateNodeRecord->childrenByContentStream[$event->contentStreamId->value]
                        ->getHierarchyHyperrelation($specializationSibling->dimensionSpacePoint);
                    if ($childRelation) {
                        $childRelation->parent = $specializationRecord;
                        $aggregateNodeRecord->childrenByContentStream[$event->contentStreamId->value]->detach($childRelation);
                        $childRelations->attach($childRelation);
                    }
                    // else there are no children yet, which is unchanged by this operation
                }
            }
        }

        /** @todo copy reference relations */
        $this->graphStructure->addNodeRecord($specializationRecord, $event->contentStreamId);
    }

    private function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo create and reconnect */
    }

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo create and reconnect */
    }

    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event, EventEnvelope $eventEnvelope): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo adjust with copy on write including timestamps */
    }

    private function whenNodeReferencesWereSet(NodeReferencesWereSet $event, EventEnvelope $eventEnvelope): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo adjust with copy on write including timestamps */
    }

    private function whenRootNodeAggregateDimensionsWereUpdated(RootNodeAggregateDimensionsWereUpdated $event): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo adjust root node coverage */
    }

    private function whenSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo add subtree tag */
        #$this->addSubtreeTag($event->contentStreamId, $event->nodeAggregateId, $event->affectedDimensionSpacePoints, $event->tag);
    }

    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
        /** @todo remove subtree tag */
    }

    private function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        // legacy handling:
        // before https://github.com/neos/neos-development-collection/pull/4965 this event was emitted and set the content stream status to `REBASE_ERROR`
        // instead of setting the error state on replay for old events we make it almost behave like if the rebase had failed today: reopen the workspaces content stream id
        // the candidateContentStreamId will be removed by the ContentStreamPruner
        $this->contentStreamRegistry->reopenContentStream($event->sourceContentStreamId);
    }

    private static function resolveInitiatingDateTime(EventEnvelope $eventEnvelope): \DateTimeImmutable
    {
        if ($eventEnvelope->event->metadata?->has(InitiatingEventMetadata::INITIATING_TIMESTAMP) !== true) {
            return $eventEnvelope->recordedAt;
        }
        $initiatingTimestamp = InitiatingEventMetadata::getInitiatingTimestamp($eventEnvelope->event->metadata);
        if ($initiatingTimestamp === null) {
            throw new \RuntimeException(sprintf('Failed to extract initiating timestamp from event "%s"', $eventEnvelope->event->id->value), 1678902291);
        }
        return $initiatingTimestamp;
    }
}
