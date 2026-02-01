<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamDbId;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSibling;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * The NodeMove projection feature trait
 *
 * @internal
 */
trait NodeMove
{
    use SubtreeTagging;

    private function moveNodeAggregate(ContentStreamDbId $contentStreamDbId, NodeAggregateId $nodeAggregateId, ?NodeAggregateId $newParentNodeAggregateId, InterdimensionalSiblings $succeedingSiblingsForCoverage): void
    {
        foreach ($succeedingSiblingsForCoverage as $succeedingSiblingForCoverage) {
            $nodeToBeMoved = $this->projectionContentGraph->findNodeInAggregate(
                $contentStreamDbId,
                $nodeAggregateId,
                $succeedingSiblingForCoverage->dimensionSpacePoint
            );

            if (is_null($nodeToBeMoved)) {
                throw new \RuntimeException(sprintf('Failed to move node "%s" in sub graph %s@%s because it does not exist', $nodeAggregateId->value, $succeedingSiblingForCoverage->dimensionSpacePoint->toJson(), $contentStreamDbId->value), 1716471638);
            }

            if ($newParentNodeAggregateId) {
                $this->moveNodeBeneathParent(
                    $contentStreamDbId,
                    $nodeToBeMoved,
                    $newParentNodeAggregateId,
                    $succeedingSiblingForCoverage
                );
                $this->moveSubtreeTags(
                    $contentStreamDbId,
                    $newParentNodeAggregateId,
                    $succeedingSiblingForCoverage->dimensionSpacePoint
                );
            } else {
                $this->moveNodeBeforeSucceedingSibling(
                    $contentStreamDbId,
                    $nodeToBeMoved,
                    $succeedingSiblingForCoverage,
                );
                // subtree tags stay the same if the parent doesn't change
            }
        }
    }

    /**
     * This helper is responsible for moving a single incoming HierarchyRelation of $nodeToBeMoved
     * to a new location without changing the parent. $succeedingSiblingForCoverage specifies
     * which incoming HierarchyRelation should be moved and where exactly.
     *
     * The move target is given as $succeedingSiblingNodeMoveTarget. This also specifies the new parent node.
     */
    private function moveNodeBeforeSucceedingSibling(
        ContentStreamDbId $contentStreamDbId,
        NodeRecord $nodeToBeMoved,
        InterdimensionalSibling $succeedingSiblingForCoverage,
    ): void {
        // find the single ingoing hierarchy relation which we want to move
        $ingoingHierarchyRelation = $this->findIngoingHierarchyRelationToBeMoved(
            $nodeToBeMoved,
            $contentStreamDbId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );

        $newSucceedingSibling = null;
        if ($succeedingSiblingForCoverage->nodeAggregateId) {
            // find the new succeeding sibling NodeRecord; We need this record because we'll use its RelationAnchorPoint later.
            $newSucceedingSibling = $this->projectionContentGraph->findNodeInAggregate(
                $contentStreamDbId,
                $succeedingSiblingForCoverage->nodeAggregateId,
                $succeedingSiblingForCoverage->dimensionSpacePoint
            );
            if ($newSucceedingSibling === null) {
                throw new \RuntimeException(sprintf('Failed to move node "%s" in sub graph %s@%s because target succeeding sibling node "%s" is missing', $nodeToBeMoved->nodeAggregateId->value, $succeedingSiblingForCoverage->dimensionSpacePoint->toJson(), $contentStreamDbId->value, $succeedingSiblingForCoverage->nodeAggregateId->value), 1716471881);
            }
        }

        // fetch...
        $newPosition = $this->getRelationPosition(
            $ingoingHierarchyRelation->parentNodeAnchor,
            null,
            $newSucceedingSibling?->relationAnchorPoint,
            $contentStreamDbId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );

        // ...and assign the new position
        $ingoingHierarchyRelation->assignNewPosition(
            $newPosition,
            $this->dbal,
            $this->tableNames
        );
    }

    /**
     * This helper is responsible for moving a single incoming HierarchyRelation of $nodeToBeMoved
     * to a new location including a change of parent. $succeedingSiblingForCoverage specifies
     * which incoming HierarchyRelation should be moved and where exactly.
     *
     * The move target is given as $parentNodeAggregateId and $succeedingSiblingForCoverage.
     * We always move beneath the parent before the succeeding sibling if given (or to the end)
     */
    private function moveNodeBeneathParent(
        ContentStreamDbId $contentStreamDbId,
        NodeRecord $nodeToBeMoved,
        NodeAggregateId $parentNodeAggregateId,
        InterdimensionalSibling $succeedingSiblingForCoverage,
    ): void {
        // find the single ingoing hierarchy relation which we want to move
        $ingoingHierarchyRelation = $this->findIngoingHierarchyRelationToBeMoved(
            $nodeToBeMoved,
            $contentStreamDbId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );

        // find the new parent NodeRecord; We need this record because we'll use its RelationAnchorPoints later.
        $newParent = $this->projectionContentGraph->findNodeInAggregate(
            $contentStreamDbId,
            $parentNodeAggregateId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );
        if ($newParent === null) {
            throw new \RuntimeException(sprintf('Failed to move node "%s" in sub graph %s@%s because target parent node is missing', $nodeToBeMoved->nodeAggregateId->value, $succeedingSiblingForCoverage->dimensionSpacePoint->toJson(), $contentStreamDbId->value), 1716471955);
        }

        $newSucceedingSibling = null;
        if ($succeedingSiblingForCoverage->nodeAggregateId) {
            // find the new succeeding sibling NodeRecord; We need this record because we'll use its RelationAnchorPoint later.
            $newSucceedingSibling = $this->projectionContentGraph->findNodeInAggregate(
                $contentStreamDbId,
                $succeedingSiblingForCoverage->nodeAggregateId,
                $succeedingSiblingForCoverage->dimensionSpacePoint
            );
            if ($newSucceedingSibling === null) {
                throw new \RuntimeException(sprintf('Failed to move node "%s" in sub graph %s@%s because target succeeding sibling node is missing', $nodeToBeMoved->nodeAggregateId->value, $succeedingSiblingForCoverage->dimensionSpacePoint->toJson(), $contentStreamDbId->value), 1716471995);
            }
        }

        // assign new position
        $newPosition = $this->getRelationPosition(
            $newParent->relationAnchorPoint,
            null,
            $newSucceedingSibling?->relationAnchorPoint,
            $contentStreamDbId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );

        // this is the actual move
        $ingoingHierarchyRelation->assignNewParentNode(
            $newParent->relationAnchorPoint,
            $newPosition,
            $this->dbal,
            $this->tableNames
        );
    }

    /**
     * Helper for the move methods.
     */
    private function findIngoingHierarchyRelationToBeMoved(
        NodeRecord $nodeToBeMoved,
        ContentStreamDbId $contentStreamDbId,
        DimensionSpacePoint $coveredDimensionSpacePointWhereMoveShouldHappen
    ): HierarchyRelation {
        $restrictToSet = DimensionSpacePointSet::fromArray([$coveredDimensionSpacePointWhereMoveShouldHappen]);
        $ingoingHierarchyRelations = $this->projectionContentGraph->findIngoingHierarchyRelationsForNode(
            $nodeToBeMoved->relationAnchorPoint,
            $contentStreamDbId,
            $restrictToSet,
        );
        if (count($ingoingHierarchyRelations) !== 1) {
            // there should always be exactly one incoming relation in the given DimensionSpacePoint; everything
            // else would be a totally wrong behavior of findIngoingHierarchyRelationsForNode().
            throw new \RuntimeException(sprintf('Failed move node "%s" in sub graph %s@%s because ingoing source hierarchy relation is missing', $nodeToBeMoved->nodeAggregateId->value, $restrictToSet->toJson(), $contentStreamDbId->value), 1716472138);
        }
        return reset($ingoingHierarchyRelations);
    }
}
