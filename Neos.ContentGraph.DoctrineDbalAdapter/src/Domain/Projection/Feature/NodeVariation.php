<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelationRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\EventStore\Model\EventEnvelope;

/**
 * The NodeVariation projection feature trait
 *
 * @internal
 */
trait NodeVariation
{
    abstract protected function getProjectionContentGraph(): ProjectionContentGraph;

    abstract protected function getTableNamePrefix(): string;

    /**
     * @param NodeSpecializationVariantWasCreated $event
     * @throws \Exception
     * @throws \Throwable
     */
    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->transactional(function () use ($event, $eventEnvelope) {
            // Do the actual specialization
            $sourceNode = $this->getProjectionContentGraph()->findNodeInAggregate(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->sourceOrigin->toDimensionSpacePoint()
            );
            if (is_null($sourceNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }

            $specializedNode = $this->copyNodeToDimensionSpacePoint(
                $sourceNode,
                $event->specializationOrigin,
                $eventEnvelope
            );

            $uncoveredDimensionSpacePoints = $event->specializationCoverage->points;
            foreach (
                $this->getProjectionContentGraph()->findIngoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamId,
                    $sourceNode->nodeAggregateId,
                    $event->specializationCoverage
                ) as $hierarchyRelation
            ) {
                $hierarchyRelation->assignNewChildNode(
                    $specializedNode->relationAnchorPoint,
                    $this->getDatabaseConnection(),
                    $this->tableNamePrefix
                );
                unset($uncoveredDimensionSpacePoints[$hierarchyRelation->dimensionSpacePointHash]);
            }
            if (!empty($uncoveredDimensionSpacePoints)) {
                $sourceParent = $this->projectionContentGraph->findParentNode(
                    $event->contentStreamId,
                    $event->nodeAggregateId,
                    $event->sourceOrigin,
                );
                if (is_null($sourceParent)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing(get_class($event));
                }
                foreach ($uncoveredDimensionSpacePoints as $uncoveredDimensionSpacePoint) {
                    $parentNode = $this->projectionContentGraph->findNodeInAggregate(
                        $event->contentStreamId,
                        $sourceParent->nodeAggregateId,
                        $uncoveredDimensionSpacePoint
                    );
                    if (is_null($parentNode)) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            get_class($event)
                        );
                    }

                    $hierarchyRelation = new HierarchyRelationRecord(
                        $parentNode->relationAnchorPoint,
                        $specializedNode->relationAnchorPoint,
                        $sourceNode->nodeName,
                        $event->contentStreamId,
                        $uncoveredDimensionSpacePoint,
                        $uncoveredDimensionSpacePoint->hash,
                        $this->projectionContentGraph->determineHierarchyRelationPosition(
                            $parentNode->relationAnchorPoint,
                            $specializedNode->relationAnchorPoint,
                            null,
                            $event->contentStreamId,
                            $uncoveredDimensionSpacePoint
                        )
                    );
                    $hierarchyRelation->addToDatabase($this->getDatabaseConnection(), $this->getTableNamePrefix());
                }
            }

            foreach (
                $this->getProjectionContentGraph()->findOutgoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamId,
                    $sourceNode->nodeAggregateId,
                    $event->specializationCoverage
                ) as $hierarchyRelation
            ) {
                $hierarchyRelation->assignNewParentNode(
                    $specializedNode->relationAnchorPoint,
                    null,
                    $this->getDatabaseConnection(),
                    $this->getTableNamePrefix()
                );
            }

            // Copy Reference Edges
            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $specializedNode->relationAnchorPoint
            );
        });
    }

    /**
     * @param NodeGeneralizationVariantWasCreated $event
     * @throws \Exception
     * @throws \Throwable
     */
    public function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->transactional(function () use ($event, $eventEnvelope) {
            // do the generalization
            $sourceNode = $this->getProjectionContentGraph()->findNodeInAggregate(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->sourceOrigin->toDimensionSpacePoint()
            );
            if (is_null($sourceNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
            $sourceParentNode = $this->getProjectionContentGraph()->findParentNode(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->sourceOrigin
            );
            if (is_null($sourceParentNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing(get_class($event));
            }
            $generalizedNode = $this->copyNodeToDimensionSpacePoint(
                $sourceNode,
                $event->generalizationOrigin,
                $eventEnvelope
            );

            $unassignedIngoingDimensionSpacePoints = $event->generalizationCoverage;
            foreach (
                $this->getProjectionContentGraph()->findIngoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamId,
                    $event->nodeAggregateId,
                    $event->generalizationCoverage
                ) as $existingIngoingHierarchyRelation
            ) {
                $existingIngoingHierarchyRelation->assignNewChildNode(
                    $generalizedNode->relationAnchorPoint,
                    $this->getDatabaseConnection(),
                    $this->tableNamePrefix
                );
                $unassignedIngoingDimensionSpacePoints = $unassignedIngoingDimensionSpacePoints->getDifference(
                    new DimensionSpacePointSet([
                        $existingIngoingHierarchyRelation->dimensionSpacePoint
                    ])
                );
            }

            foreach (
                $this->getProjectionContentGraph()->findOutgoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamId,
                    $event->nodeAggregateId,
                    $event->generalizationCoverage
                ) as $existingOutgoingHierarchyRelation
            ) {
                $existingOutgoingHierarchyRelation->assignNewParentNode(
                    $generalizedNode->relationAnchorPoint,
                    null,
                    $this->getDatabaseConnection(),
                    $this->getTableNamePrefix()
                );
            }

            if (count($unassignedIngoingDimensionSpacePoints) > 0) {
                $projectionContentGraph = $this->getProjectionContentGraph();
                $ingoingSourceHierarchyRelation = $projectionContentGraph->findIngoingHierarchyRelationsForNode(
                    $sourceNode->relationAnchorPoint,
                    $event->contentStreamId,
                    new DimensionSpacePointSet([$event->sourceOrigin->toDimensionSpacePoint()])
                )[$event->sourceOrigin->hash] ?? null;
                if (is_null($ingoingSourceHierarchyRelation)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheIngoingSourceHierarchyRelationIsMissing(
                        get_class($event)
                    );
                }
                // the null case is caught by the NodeAggregate or its command handler
                foreach ($unassignedIngoingDimensionSpacePoints as $unassignedDimensionSpacePoint) {
                    // The parent node aggregate might be varied as well,
                    // so we need to find a parent node for each covered dimension space point
                    $generalizationParentNode = $this->getProjectionContentGraph()->findNodeInAggregate(
                        $event->contentStreamId,
                        $sourceParentNode->nodeAggregateId,
                        $unassignedDimensionSpacePoint
                    );
                    if (is_null($generalizationParentNode)) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            get_class($event)
                        );
                    }

                    $this->copyHierarchyRelationToDimensionSpacePoint(
                        $ingoingSourceHierarchyRelation,
                        $event->contentStreamId,
                        $unassignedDimensionSpacePoint,
                        $generalizationParentNode->relationAnchorPoint,
                        $generalizedNode->relationAnchorPoint
                    );
                }
            }

            // Copy Reference Edges
            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $generalizedNode->relationAnchorPoint
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->transactional(function () use ($event, $eventEnvelope) {
            // Do the peer variant creation itself
            $sourceNode = $this->getProjectionContentGraph()->findNodeInAggregate(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->sourceOrigin->toDimensionSpacePoint()
            );
            if (is_null($sourceNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
            $sourceParentNode = $this->getProjectionContentGraph()->findParentNode(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->sourceOrigin
            );
            if (is_null($sourceParentNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing(get_class($event));
            }
            $peerNode = $this->copyNodeToDimensionSpacePoint(
                $sourceNode,
                $event->peerOrigin,
                $eventEnvelope
            );

            $unassignedIngoingDimensionSpacePoints = $event->peerCoverage;
            foreach (
                $this->getProjectionContentGraph()->findIngoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamId,
                    $event->nodeAggregateId,
                    $event->peerCoverage
                ) as $existingIngoingHierarchyRelation
            ) {
                $existingIngoingHierarchyRelation->assignNewChildNode(
                    $peerNode->relationAnchorPoint,
                    $this->getDatabaseConnection(),
                    $this->tableNamePrefix
                );
                $unassignedIngoingDimensionSpacePoints = $unassignedIngoingDimensionSpacePoints->getDifference(
                    new DimensionSpacePointSet([
                        $existingIngoingHierarchyRelation->dimensionSpacePoint
                    ])
                );
            }

            foreach (
                $this->getProjectionContentGraph()->findOutgoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamId,
                    $event->nodeAggregateId,
                    $event->peerCoverage
                ) as $existingOutgoingHierarchyRelation
            ) {
                $existingOutgoingHierarchyRelation->assignNewParentNode(
                    $peerNode->relationAnchorPoint,
                    null,
                    $this->getDatabaseConnection(),
                    $this->getTableNamePrefix()
                );
            }

            foreach ($unassignedIngoingDimensionSpacePoints as $coveredDimensionSpacePoint) {
                // The parent node aggregate might be varied as well,
                // so we need to find a parent node for each covered dimension space point
                $peerParentNode = $this->getProjectionContentGraph()->findNodeInAggregate(
                    $event->contentStreamId,
                    $sourceParentNode->nodeAggregateId,
                    $coveredDimensionSpacePoint
                );
                if (is_null($peerParentNode)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(get_class($event));
                }

                $this->connectHierarchy(
                    $event->contentStreamId,
                    $peerParentNode->relationAnchorPoint,
                    $peerNode->relationAnchorPoint,
                    new DimensionSpacePointSet([$coveredDimensionSpacePoint]),
                    null, // @todo fetch appropriate sibling
                    $sourceNode->nodeName
                );
            }

            // Copy Reference Edges
            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $peerNode->relationAnchorPoint
            );
        });
    }

    abstract protected function copyNodeToDimensionSpacePoint(
        NodeRecord $sourceNode,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        EventEnvelope $eventEnvelope,
    ): NodeRecord;

    abstract protected function copyHierarchyRelationToDimensionSpacePoint(
        HierarchyRelationRecord $sourceHierarchyRelation,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        ?NodeRelationAnchorPoint $newParent = null,
        ?NodeRelationAnchorPoint $newChild = null
    ): HierarchyRelationRecord;

    abstract protected function connectHierarchy(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $parentNodeAnchorPoint,
        NodeRelationAnchorPoint $childNodeAnchorPoint,
        DimensionSpacePointSet $dimensionSpacePointSet,
        ?NodeRelationAnchorPoint $succeedingSiblingNodeAnchorPoint,
        NodeName $relationName = null
    ): void;

    abstract protected function copyReferenceRelations(
        NodeRelationAnchorPoint $sourceRelationAnchorPoint,
        NodeRelationAnchorPoint $destinationRelationAnchorPoint
    ): void;

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
