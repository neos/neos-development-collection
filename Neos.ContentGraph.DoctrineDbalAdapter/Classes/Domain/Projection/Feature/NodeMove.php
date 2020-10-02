<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasMoved;

/**
 * The NodeMove projection feature trait
 *
 * Requires RestrictionRelations to work
 */
trait NodeMove
{
    /**
     * @var ProjectionContentGraph
     */
    protected $projectionContentGraph;

    /**
     * @param NodeAggregateWasMoved $event
     * @throws \Throwable
     */
    public function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event)
    {
        $this->transactional(function () use ($event) {
            foreach ($event->getNodeMoveMappings() as $moveNodeMapping) {
                // for each materialized node in the DB which we want to adjust, we have one MoveNodeMapping.

                $nodeToBeMoved = $this->projectionContentGraph->findNodeByIdentifiers(
                    $event->getContentStreamIdentifier(),
                    $event->getNodeAggregateIdentifier(),
                    $moveNodeMapping->getMovedNodeOrigin()
                );

                // we build up the $coveredDimensionSpacePoints (i.e. the incoming edges) for the node which we want to move
                $ingoingHierarchyRelations = $this->projectionContentGraph->findIngoingHierarchyRelationsForNode($nodeToBeMoved->relationAnchorPoint, $event->getContentStreamIdentifier());
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
                    $newParentAssignment = $moveNodeMapping->getNewParentAssignments()->get($coveredDimensionSpacePoint);
                    if ($newParentAssignment) {
                        // $newParentNodes[$coveredDimensionSpacePoint->getHash()] might be null (if the parent is not visible in this DimensionSpacePoint)
                        $newParentNodes[$coveredDimensionSpacePoint->getHash()] = $this->projectionContentGraph->findNodeInAggregate(
                            $event->getContentStreamIdentifier(),
                            $newParentAssignment->getNodeAggregateIdentifier(),
                            $coveredDimensionSpacePoint
                        );
                        $affectedHierarchyDimensionSpacePoints[] = $coveredDimensionSpacePoint;
                    }
                }

                // because we will reassign the parent relations, we have to clear the restiction relations for these.
                $this->removeAllRestrictionRelationsInSubtreeImposedByAncestors(
                    $event->getContentStreamIdentifier(),
                    $event->getNodeAggregateIdentifier(),
                    new DimensionSpacePointSet($affectedHierarchyDimensionSpacePoints)
                );

                // if we want to change the succeeding siblings for this DimensionSpacePoint, we find the
                // corresponding succeeding sibling node (because we lateron need its relationAnchorPoint)
                $newSucceedingSiblingNodes = [];
                foreach ($moveNodeMapping->getNewSucceedingSiblingAssignments() as $coveredDimensionSpacePointHash => $newSucceedingSiblingAssignment) {
                    $newSucceedingSiblingNodes[$coveredDimensionSpacePointHash] = $this->projectionContentGraph->findNodeByIdentifiers(
                        $event->getContentStreamIdentifier(),
                        $newSucceedingSiblingAssignment->getNodeAggregateIdentifier(),
                        $newSucceedingSiblingAssignment->getOriginDimensionSpacePoint()
                    );
                }

                foreach ($coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
                    $ingoingHierarchyRelation = $ingoingHierarchyRelations[$coveredDimensionSpacePoint->getHash()];
                    if (isset($newParentNodes[$coveredDimensionSpacePoint->getHash()])) {
                        // CASE: we want to connect this hierarchy relation to a new parent.
                        $newParentNode = $newParentNodes[$coveredDimensionSpacePoint->getHash()];
                        $newSucceedingSibling = $newSucceedingSiblingNodes[$coveredDimensionSpacePoint->getHash()] ?? null;
                        $newPosition = $this->getRelationPosition(
                            $newParentNode->relationAnchorPoint,
                            null,
                            $newSucceedingSibling ? $newSucceedingSibling->relationAnchorPoint : null,
                            $event->getContentStreamIdentifier(),
                            $coveredDimensionSpacePoint
                        );

                        // this is the actual move
                        $ingoingHierarchyRelation->assignNewParentNode($newParentNodes[$coveredDimensionSpacePoint->getHash()]->relationAnchorPoint, $newPosition, $this->getDatabaseConnection());

                        // re-build restriction relations
                        $this->cascadeRestrictionRelations(
                            $event->getContentStreamIdentifier(),
                            $newParentNode->nodeAggregateIdentifier,
                            $event->getNodeAggregateIdentifier(),
                            new DimensionSpacePointSet([$coveredDimensionSpacePoint])
                        );
                    } elseif (isset($newSucceedingSiblingNodes[$coveredDimensionSpacePoint->getHash()])) {
                        // CASE: there is no new parent node, so we want to move within the EXISTING parent node (sorting)

                        $newSucceedingSibling = $newSucceedingSiblingNodes[$coveredDimensionSpacePoint->getHash()];
                        $newPosition = $this->getRelationPosition(
                            null,
                            $nodeToBeMoved->relationAnchorPoint,
                            $newSucceedingSibling->relationAnchorPoint,
                            $event->getContentStreamIdentifier(),
                            $coveredDimensionSpacePoint
                        );

                        // this is the actual move
                        $ingoingHierarchyRelation->assignNewPosition($newPosition, $this->getDatabaseConnection());

                    // NOTE: we do not need to re-build restriction relations because the hierarchy does not change.
                    } elseif ($event->getRepositionNodesWithoutAssignments()->contains($coveredDimensionSpacePoint)) {
                        // CASE: we move to the end of all its siblings.

                        $newPosition = $this->getRelationPosition(
                            null,
                            $nodeToBeMoved->relationAnchorPoint,
                            null,
                            $event->getContentStreamIdentifier(),
                            $coveredDimensionSpacePoint
                        );
                        $ingoingHierarchyRelation->assignNewPosition($newPosition, $this->getDatabaseConnection());
                    }
                }
            }
        });
    }

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(callable $operations): void;
}
