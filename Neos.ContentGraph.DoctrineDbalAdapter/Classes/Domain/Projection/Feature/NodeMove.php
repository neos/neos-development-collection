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
                $nodeToBeMoved = $this->projectionContentGraph->findNodeByIdentifiers(
                    $event->getContentStreamIdentifier(),
                    $event->getNodeAggregateIdentifier(),
                    $moveNodeMapping->getMovedNodeOrigin()
                );

                $ingoingHierarchyRelations = $this->projectionContentGraph->findIngoingHierarchyRelationsForNode($nodeToBeMoved->relationAnchorPoint, $event->getContentStreamIdentifier());
                $coveredDimensionSpacePoints = [];
                foreach ($ingoingHierarchyRelations as $ingoingHierarchyRelation) {
                    $coveredDimensionSpacePoints[] = $ingoingHierarchyRelation->dimensionSpacePoint;
                }
                $coveredDimensionSpacePoints = new DimensionSpacePointSet($coveredDimensionSpacePoints);

                $affectedHierarchyDimensionSpacePoints = [];
                /** @var array|NodeRecord[] $newParentNodes */
                $newParentNodes = [];
                foreach ($coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
                    $newParentAssignment = $moveNodeMapping->getNewParentAssignments()->get($coveredDimensionSpacePoint);
                    if ($newParentAssignment) {
                        $newParentNodes[$coveredDimensionSpacePoint->getHash()] = $this->projectionContentGraph->findNodeInAggregate(
                            $event->getContentStreamIdentifier(),
                            $newParentAssignment->getNodeAggregateIdentifier(),
                            $coveredDimensionSpacePoint
                        );
                        $affectedHierarchyDimensionSpacePoints[] = $coveredDimensionSpacePoint;
                    }
                }

                $this->removeAllRestrictionRelationsInSubtreeImposedByAncestors(
                    $event->getContentStreamIdentifier(),
                    $event->getNodeAggregateIdentifier(),
                    new DimensionSpacePointSet($affectedHierarchyDimensionSpacePoints)
                );

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
                        $newParentNode = $newParentNodes[$coveredDimensionSpacePoint->getHash()];
                        $newSucceedingSibling = $newSucceedingSiblingNodes[$coveredDimensionSpacePoint->getHash()] ?? null;
                        $newPosition = $this->getRelationPosition(
                            $newParentNode->relationAnchorPoint,
                            null,
                            $newSucceedingSibling ? $newSucceedingSibling->relationAnchorPoint : null,
                            $event->getContentStreamIdentifier(),
                            $coveredDimensionSpacePoint
                        );

                        $ingoingHierarchyRelation->assignNewParentNode($newParentNodes[$coveredDimensionSpacePoint->getHash()]->relationAnchorPoint, $newPosition, $this->getDatabaseConnection());

                        $this->cascadeRestrictionRelations(
                            $event->getContentStreamIdentifier(),
                            $newParentNode->nodeAggregateIdentifier,
                            $event->getNodeAggregateIdentifier(),
                            new DimensionSpacePointSet([$coveredDimensionSpacePoint])
                        );
                    } elseif (isset($newSucceedingSiblingNodes[$coveredDimensionSpacePoint->getHash()])) {
                        $newSucceedingSibling = $newSucceedingSiblingNodes[$coveredDimensionSpacePoint->getHash()];
                        $newPosition = $this->getRelationPosition(
                            null,
                            $nodeToBeMoved->relationAnchorPoint,
                            $newSucceedingSibling->relationAnchorPoint,
                            $event->getContentStreamIdentifier(),
                            $coveredDimensionSpacePoint
                        );
                        $ingoingHierarchyRelation->assignNewPosition($newPosition, $this->getDatabaseConnection());
                    } elseif ($event->getRepositionNodesWithoutAssignments()->contains($coveredDimensionSpacePoint)) {
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
