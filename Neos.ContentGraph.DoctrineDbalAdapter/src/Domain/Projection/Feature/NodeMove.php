<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSibling;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
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

    /**
     * @param NodeAggregateWasMoved $event
     * @throws \Throwable
     */
    private function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        $this->transactional(function () use ($event) {
            foreach ($event->succeedingSiblingsForCoverage as $succeedingSiblingForCoverage) {
                $nodeToBeMoved = $this->getProjectionContentGraph()->findNodeInAggregate(
                    $event->contentStreamId,
                    $event->nodeAggregateId,
                    $succeedingSiblingForCoverage->dimensionSpacePoint
                );

                if (is_null($nodeToBeMoved)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
                }

                if ($event->newParentNodeAggregateId) {
                    $this->moveNodeBeneathParent(
                        $event->contentStreamId,
                        $nodeToBeMoved,
                        $event->newParentNodeAggregateId,
                        $succeedingSiblingForCoverage
                    );
                    $this->moveSubtreeTags(
                        $event->contentStreamId,
                        $event->newParentNodeAggregateId,
                        $succeedingSiblingForCoverage->dimensionSpacePoint
                    );
                } else {
                    $this->moveNodeBeforeSucceedingSibling(
                        $event->contentStreamId,
                        $nodeToBeMoved,
                        $succeedingSiblingForCoverage,
                    );
                    // subtree tags stay the same if the parent doesn't change
                }
            }
        });
    }

    /**
     * This helper is responsible for moving a single incoming HierarchyRelation of $nodeToBeMoved
     * to a new location without changing the parent. $succeedingSiblingForCoverage specifies
     * which incoming HierarchyRelation should be moved and where exactly.
     *
     * The move target is given as $succeedingSiblingNodeMoveTarget. This also specifies the new parent node.
     * @throws \Exception
     */
    private function moveNodeBeforeSucceedingSibling(
        ContentStreamId $contentStreamId,
        NodeRecord $nodeToBeMoved,
        InterdimensionalSibling $succeedingSiblingForCoverage,
    ): void {
        $projectionContentGraph = $this->getProjectionContentGraph();

        // find the single ingoing hierarchy relation which we want to move
        $ingoingHierarchyRelation = $this->findIngoingHierarchyRelationToBeMoved(
            $nodeToBeMoved,
            $contentStreamId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );

        $newSucceedingSibling = null;
        if ($succeedingSiblingForCoverage->nodeAggregateId) {
            // find the new succeeding sibling NodeRecord; We need this record because we'll use its RelationAnchorPoint later.
            $newSucceedingSibling = $projectionContentGraph->findNodeInAggregate(
                $contentStreamId,
                $succeedingSiblingForCoverage->nodeAggregateId,
                $succeedingSiblingForCoverage->dimensionSpacePoint
            );
            if ($newSucceedingSibling === null) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetSucceedingSiblingNodeIsMissing(
                    MoveNodeAggregate::class
                );
            }
        }

        // fetch...
        $newPosition = $this->getRelationPosition(
            $ingoingHierarchyRelation->parentNodeAnchor,
            null,
            $newSucceedingSibling?->relationAnchorPoint,
            $contentStreamId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );

        // ...and assign the new position
        $ingoingHierarchyRelation->assignNewPosition(
            $newPosition,
            $this->getDatabaseConnection(),
            $this->contentGraphTableNames
        );
    }

    /**
     * This helper is responsible for moving a single incoming HierarchyRelation of $nodeToBeMoved
     * to a new location including a change of parent. $succeedingSiblingForCoverage specifies
     * which incoming HierarchyRelation should be moved and where exactly.
     *
     * The move target is given as $parentNodeAggregateId and $succeedingSiblingForCoverage.
     * We always move beneath the parent before the succeeding sibling if given (or to the end)
     * @throws DBALException
     */
    private function moveNodeBeneathParent(
        ContentStreamId $contentStreamId,
        NodeRecord $nodeToBeMoved,
        NodeAggregateId $parentNodeAggregateId,
        InterdimensionalSibling $succeedingSiblingForCoverage,
    ): void {
        $projectionContentGraph = $this->getProjectionContentGraph();

        // find the single ingoing hierarchy relation which we want to move
        $ingoingHierarchyRelation = $this->findIngoingHierarchyRelationToBeMoved(
            $nodeToBeMoved,
            $contentStreamId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );

        // find the new parent NodeRecord; We need this record because we'll use its RelationAnchorPoints later.
        $newParent = $projectionContentGraph->findNodeInAggregate(
            $contentStreamId,
            $parentNodeAggregateId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );
        if ($newParent === null) {
            throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                MoveNodeAggregate::class
            );
        }

        $newSucceedingSibling = null;
        if ($succeedingSiblingForCoverage->nodeAggregateId) {
            // find the new succeeding sibling NodeRecord; We need this record because we'll use its RelationAnchorPoint later.
            $newSucceedingSibling = $projectionContentGraph->findNodeInAggregate(
                $contentStreamId,
                $succeedingSiblingForCoverage->nodeAggregateId,
                $succeedingSiblingForCoverage->dimensionSpacePoint
            );
            if ($newSucceedingSibling === null) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetSucceedingSiblingNodeIsMissing(
                    MoveNodeAggregate::class
                );
            }
        }

        // assign new position
        $newPosition = $this->getRelationPosition(
            $newParent->relationAnchorPoint,
            null,
            $newSucceedingSibling?->relationAnchorPoint,
            $contentStreamId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );

        // this is the actual move
        $ingoingHierarchyRelation->assignNewParentNode(
            $newParent->relationAnchorPoint,
            $newPosition,
            $this->getDatabaseConnection(),
            $this->contentGraphTableNames
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
