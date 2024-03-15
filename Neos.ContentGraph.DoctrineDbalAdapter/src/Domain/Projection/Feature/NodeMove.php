<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\ParentNodeMoveDestination;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\SucceedingSiblingNodeMoveDestination;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The NodeMove projection feature trait
 *
 * @internal
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
            foreach ($event->nodeMoveMappings as $moveNodeMapping) {
                // for each materialized node in the DB which we want to adjust, we have one MoveNodeMapping.
                $nodeToBeMoved = $this->getProjectionContentGraph()->findNodeByIds(
                    $event->contentStreamId,
                    $event->nodeAggregateId,
                    $moveNodeMapping->movedNodeOrigin
                );

                if (is_null($nodeToBeMoved)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
                }

                foreach ($moveNodeMapping->newLocations as $newLocation) {
                    assert($newLocation instanceof CoverageNodeMoveMapping);

                    $affectedDimensionSpacePoints = new DimensionSpacePointSet([
                        $newLocation->coveredDimensionSpacePoint
                    ]);

                    // do the move (depending on how the move target is specified)
                    $newParentNodeAggregateId = match ($newLocation->destination::class) {
                        SucceedingSiblingNodeMoveDestination::class => $this->moveNodeBeforeSucceedingSibling(
                            $event->contentStreamId,
                            $nodeToBeMoved,
                            $newLocation->coveredDimensionSpacePoint,
                            $newLocation->destination
                        ),
                        ParentNodeMoveDestination::class => $newLocation->destination->nodeAggregateId,
                    };
                    if ($newLocation->destination instanceof ParentNodeMoveDestination) {
                        $this->moveNodeIntoParent(
                            $event->contentStreamId,
                            $nodeToBeMoved,
                            $newLocation->coveredDimensionSpacePoint,
                            $newLocation->destination
                        );
                    }
                    $this->moveSubtreeTags($event->contentStreamId, $event->nodeAggregateId, $newParentNodeAggregateId, $newLocation->coveredDimensionSpacePoint);
                }
            }
        });
    }

    /**
     * This helper is responsible for moving a single incoming HierarchyRelation of $nodeToBeMoved
     * to a new location. $coveredDimensionSpacePointWhereMoveShouldHappen specifies which incoming HierarchyRelation
     * should be moved.
     *
     * The move target is given as $succeedingSiblingNodeMoveTarget. This also specifies the new parent node.
     * @throws \Exception
     * @throws Exception
     * @return NodeAggregateId the PARENT's NodeAggregateId
     */
    private function moveNodeBeforeSucceedingSibling(
        ContentStreamId $contentStreamId,
        NodeRecord $nodeToBeMoved,
        DimensionSpacePoint $coveredDimensionSpacePointWhereMoveShouldHappen,
        SucceedingSiblingNodeMoveDestination $succeedingSiblingNodeMoveDestination,
    ): NodeAggregateId {
        $projectionContentGraph = $this->getProjectionContentGraph();

        // find the single ingoing hierarchy relation which we want to move
        $ingoingHierarchyRelation = $this->findIngoingHierarchyRelationToBeMoved(
            $nodeToBeMoved,
            $contentStreamId,
            $coveredDimensionSpacePointWhereMoveShouldHappen
        );

        // find the new succeeding sibling NodeRecord; and the new parent NodeRecord (which is the
        // succeeding sibling's parent). We need these records because we'll use their RelationAnchorPoints
        // later.
        $newSucceedingSibling = $projectionContentGraph->findNodeByIds(
            $contentStreamId,
            $succeedingSiblingNodeMoveDestination->nodeAggregateId,
            $succeedingSiblingNodeMoveDestination->originDimensionSpacePoint
        );
        if ($newSucceedingSibling === null) {
            throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetSucceedingSiblingNodeIsMissing(
                MoveNodeAggregate::class
            );
        }

        $newParent = $projectionContentGraph->findNodeByIds(
            $contentStreamId,
            $succeedingSiblingNodeMoveDestination->parentNodeAggregateId,
            $succeedingSiblingNodeMoveDestination->parentOriginDimensionSpacePoint,
        );
        if ($newParent === null) {
            // this should NEVER happen; because this would mean $newSucceedingSibling
            // wouldn't have a parent => invariant violation.
            throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetSucceedingSiblingNodesParentIsMissing(
                MoveNodeAggregate::class
            );
        }

        // assign new position
        $newPosition = $this->getRelationPosition(
            $newParent->relationAnchorPoint,
            null,
            $newSucceedingSibling->relationAnchorPoint,
            $contentStreamId,
            $coveredDimensionSpacePointWhereMoveShouldHappen
        );

        // this is the actual move
        $ingoingHierarchyRelation->assignNewParentNode(
            $newParent->relationAnchorPoint,
            $newPosition,
            $this->getDatabaseConnection(),
            $this->getTableNamePrefix()
        );

        return $newParent->nodeAggregateId;
    }

    /**
     * This helper is responsible for moving a single incoming HierarchyRelation of $nodeToBeMoved
     * to a new location. $coveredDimensionSpacePointWhereMoveShouldHappen specifies which incoming HierarchyRelation
     * should be moved.
     *
     * The move target is given as $parentNodeMoveTarget. We always move to the END of the children list of the
     * given parent.
     * @throws DBALException
     */
    private function moveNodeIntoParent(
        ContentStreamId $contentStreamId,
        NodeRecord $nodeToBeMoved,
        DimensionSpacePoint $coveredDimensionSpacePointWhereMoveShouldHappen,
        ParentNodeMoveDestination $parentNodeMoveDestination
    ): void {
        $projectionContentGraph = $this->getProjectionContentGraph();

        // find the single ingoing hierarchy relation which we want to move
        $ingoingHierarchyRelation = $this->findIngoingHierarchyRelationToBeMoved(
            $nodeToBeMoved,
            $contentStreamId,
            $coveredDimensionSpacePointWhereMoveShouldHappen
        );

        // find the new parent NodeRecord (specified by $parentNodeMoveTarget).
        // We need this record because we'll use its RelationAnchorPoints later.
        $newParent = $projectionContentGraph->findNodeByIds(
            $contentStreamId,
            $parentNodeMoveDestination->nodeAggregateId,
            $parentNodeMoveDestination->originDimensionSpacePoint
        );
        if ($newParent === null) {
            throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                MoveNodeAggregate::class
            );
        }

        // assign new position
        $newPosition = $this->getRelationPosition(
            $newParent->relationAnchorPoint,
            null,
            // move to end of children
            null,
            $contentStreamId,
            $coveredDimensionSpacePointWhereMoveShouldHappen
        );

        // this is the actual move
        $ingoingHierarchyRelation->assignNewParentNode(
            $newParent->relationAnchorPoint,
            $newPosition,
            $this->getDatabaseConnection(),
            $this->getTableNamePrefix()
        );
    }

    /**
     * Helper for the move methods.
     *
     * @param NodeRecord $nodeToBeMoved
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePoint $coveredDimensionSpacePointWhereMoveShouldHappen
     * @return HierarchyRelation
     * @throws DBALException
     */
    private function findIngoingHierarchyRelationToBeMoved(
        NodeRecord $nodeToBeMoved,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $coveredDimensionSpacePointWhereMoveShouldHappen
    ): HierarchyRelation {
        $ingoingHierarchyRelations = $this->getProjectionContentGraph()->findIngoingHierarchyRelationsForNode(
            $nodeToBeMoved->relationAnchorPoint,
            $contentStreamId,
            DimensionSpacePointSet::fromArray([$coveredDimensionSpacePointWhereMoveShouldHappen])
        );
        if (count($ingoingHierarchyRelations) !== 1) {
            // there should always be exactly one incoming relation in the given DimensionSpacePoint; everything
            // else would be a totally wrong behavior of findIngoingHierarchyRelationsForNode().
            throw EventCouldNotBeAppliedToContentGraph::becauseTheIngoingSourceHierarchyRelationIsMissing(
                MoveNodeAggregate::class
            );
        }
        return reset($ingoingHierarchyRelations);
    }


    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
