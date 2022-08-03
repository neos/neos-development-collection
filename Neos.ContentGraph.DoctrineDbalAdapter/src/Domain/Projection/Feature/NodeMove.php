<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeAggregateWasMoved;

/**
 * The NodeMove projection feature trait
 *
 * Requires RestrictionRelations to work
 */
trait NodeMove
{
    abstract protected function getProjectionContentGraph(): ProjectionContentGraph;

    abstract protected function getTableNamePrefix(): string;

    /**
     * @param NodeAggregateWasMoved $event
     * @throws \Throwable
     */
    private function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        $this->transactional(function () use ($event) {
            if ($event->nodeMoveMappings) {
                foreach ($event->nodeMoveMappings as $moveNodeMapping) {
                    // for each materialized node in the DB which we want to adjust, we have one MoveNodeMapping.

                    $nodeToBeMoved = $this->getProjectionContentGraph()->findNodeByIdentifiers(
                        $event->getContentStreamIdentifier(),
                        $event->getNodeAggregateIdentifier(),
                        $moveNodeMapping->getMovedNodeOrigin()
                    );
                    if (is_null($nodeToBeMoved)) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
                    }

                    // we build up the $coveredDimensionSpacePoints (i.e. the incoming edges)
                    // for the node which we want to move
                    $ingoingHierarchyRelations = $this->getProjectionContentGraph()->findIngoingHierarchyRelationsForNode(
                        $nodeToBeMoved->relationAnchorPoint,
                        $event->getContentStreamIdentifier()
                    );
                    $coveredDimensionSpacePoints = [];
                    foreach ($ingoingHierarchyRelations as $ingoingHierarchyRelation) {
                        $coveredDimensionSpacePoints[] = $ingoingHierarchyRelation->dimensionSpacePoint;
                    }
                    $coveredDimensionSpacePoints = new DimensionSpacePointSet($coveredDimensionSpacePoints);


                    // these are the DimensionSpacePoints for the node which will get new parents assigned.
                    $affectedHierarchyDimensionSpacePoints = [];
                    /** @var array|NodeRecord[] $newParentNodes */
                    $newParentNodes = [];
                    foreach ($coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
                        // if we want to change the parent for this DimensionSpacePoint, then we find the
                        // corresponding new parent node (because we lateron need its relationAnchorPoint)
                        $newParentAssignment = $moveNodeMapping->getNewParentAssignments()->get(
                            $coveredDimensionSpacePoint
                        );
                        if ($newParentAssignment) {
                            // $newParentNodes[$coveredDimensionSpacePoint->hash] might be null
                            // (if the parent is not visible in this DimensionSpacePoint)
                            $newParentNodes[$coveredDimensionSpacePoint->hash] = $this->getProjectionContentGraph()
                                ->findNodeInAggregate(
                                    $event->getContentStreamIdentifier(),
                                    $newParentAssignment->nodeAggregateIdentifier,
                                    $coveredDimensionSpacePoint
                                );
                            $affectedHierarchyDimensionSpacePoints[] = $coveredDimensionSpacePoint;
                        }
                    }

                    // because we will reassign the parent relations,
                    // we have to clear the restiction relations for these.
                    $this->removeAllRestrictionRelationsInSubtreeImposedByAncestors(
                        $event->getContentStreamIdentifier(),
                        $event->getNodeAggregateIdentifier(),
                        new DimensionSpacePointSet(
                            $affectedHierarchyDimensionSpacePoints
                        )
                    );

                    // if we want to change the succeeding siblings for this DimensionSpacePoint, we find the
                    // corresponding succeeding sibling node (because we lateron need its relationAnchorPoint)
                    $newSucceedingSiblingNodes = [];
                    $assignments = $moveNodeMapping->getNewSucceedingSiblingAssignments();
                    foreach ($assignments as $coveredDimensionSpacePointHash => $newSucceedingSiblingAssignment) {
                        $newSucceedingSiblingNodes[$coveredDimensionSpacePointHash]
                            = $this->getProjectionContentGraph()->findNodeByIdentifiers(
                                $event->getContentStreamIdentifier(),
                                $newSucceedingSiblingAssignment->nodeAggregateIdentifier,
                                $newSucceedingSiblingAssignment->originDimensionSpacePoint
                            );
                    }

                    foreach ($coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
                        $ingoingHierarchyRelation = $ingoingHierarchyRelations[$coveredDimensionSpacePoint->hash];
                        if (isset($newParentNodes[$coveredDimensionSpacePoint->hash])) {
                            // CASE: we want to connect this hierarchy relation to a new parent.
                            $newParentNode = $newParentNodes[$coveredDimensionSpacePoint->hash];
                            $newSucceedingSibling = $newSucceedingSiblingNodes[$coveredDimensionSpacePoint->hash]
                                ?? null;
                            $newPosition = $this->getRelationPosition(
                                $newParentNode->relationAnchorPoint,
                                null,
                                $newSucceedingSibling?->relationAnchorPoint,
                                $event->getContentStreamIdentifier(),
                                $coveredDimensionSpacePoint
                            );

                            // this is the actual move
                            $ingoingHierarchyRelation->assignNewParentNode(
                                $newParentNodes[$coveredDimensionSpacePoint->hash]->relationAnchorPoint,
                                $newPosition,
                                $this->getDatabaseConnection(),
                                $this->getTableNamePrefix()
                            );

                            // re-build restriction relations
                            $this->cascadeRestrictionRelations(
                                $event->getContentStreamIdentifier(),
                                $newParentNode->nodeAggregateIdentifier,
                                $event->getNodeAggregateIdentifier(),
                                new DimensionSpacePointSet([
                                    $coveredDimensionSpacePoint
                                ])
                            );
                        } elseif (isset($newSucceedingSiblingNodes[$coveredDimensionSpacePoint->hash])) {
                            // CASE: there is no new parent node,
                            // so we want to move within the EXISTING parent node (sorting)

                            $newSucceedingSibling = $newSucceedingSiblingNodes[$coveredDimensionSpacePoint->hash];
                            $newPosition = $this->getRelationPosition(
                                null,
                                $nodeToBeMoved->relationAnchorPoint,
                                $newSucceedingSibling->relationAnchorPoint,
                                $event->getContentStreamIdentifier(),
                                $coveredDimensionSpacePoint
                            );

                            // this is the actual move
                            $ingoingHierarchyRelation->assignNewPosition(
                                $newPosition,
                                $this->getDatabaseConnection(),
                                $this->getTableNamePrefix()
                            );

                        // NOTE: we do not need to re-build restriction relations because the hierarchy does not change.
                        } elseif (
                            $event->repositionNodesWithoutAssignments->contains($coveredDimensionSpacePoint)
                        ) {
                            // CASE: we move to the end of all its siblings.

                            $newPosition = $this->getRelationPosition(
                                null,
                                $nodeToBeMoved->relationAnchorPoint,
                                null,
                                $event->getContentStreamIdentifier(),
                                $coveredDimensionSpacePoint
                            );
                            $ingoingHierarchyRelation->assignNewPosition(
                                $newPosition,
                                $this->getDatabaseConnection(),
                                $this->getTableNamePrefix()
                            );
                        }
                    }
                }
            } else {
                // @todo handle getRepositionNodesWithoutAssignments anyway
            }
        });
    }

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
