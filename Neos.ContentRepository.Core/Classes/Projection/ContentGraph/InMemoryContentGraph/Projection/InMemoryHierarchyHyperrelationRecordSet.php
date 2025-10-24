<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;

/**
 * The active record for assigning parent nodes to child nodes in-memory
 *
 * @extends \SplObjectStorage<InMemoryHierarchyHyperrelationRecord,null>
 * @internal
 */
final class InMemoryHierarchyHyperrelationRecordSet extends \SplObjectStorage
{
    public function getCoveredDimensionSpacePointSet(): DimensionSpacePointSet
    {
        $dimensionSpacePoints = [];
        foreach ($this as $relation) {
            $dimensionSpacePoints[] = $relation->dimensionSpacePoint;
        }

        return DimensionSpacePointSet::fromArray($dimensionSpacePoints);
    }

    public function getHierarchyHyperrelation(DimensionSpacePoint $dimensionSpacePoint): ?InMemoryHierarchyHyperrelationRecord
    {
        foreach ($this as $relation) {
            if ($relation->dimensionSpacePoint === $dimensionSpacePoint) {
                return $relation;
            }
        }

        return null;
    }

    public function getParentNodeAggregateIds(): NodeAggregateIds
    {
        return NodeAggregateIds::fromArray(array_filter(array_map(
            fn (InMemoryHierarchyHyperrelationRecord $relation): ?NodeAggregateId => $relation->parent?->nodeAggregateId,
            iterator_to_array($this),
        )));
    }
}
