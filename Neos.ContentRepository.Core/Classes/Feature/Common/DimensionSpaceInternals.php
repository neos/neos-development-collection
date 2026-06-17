<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;

/**
 * @internal implementation details of command handlers
 */
trait DimensionSpaceInternals
{
    /**
     * A node aggregate's order of {@see NodeAggregate::$occupiedDimensionSpacePoints} is undefined as returned from the database.
     * Before using this unorder to emit events we use the interdimensional variation graph to order them into a flattened tree according to configuration.
     * FIXME: This method might make sense on the InterDimensionVariationGraph but for this an explicit distinctions of unordered Set and List value objects for dimension space points is required. Currently we have Set value objects that _still_ have sometimes an order with meaning.
     */
    protected function requireOrderedOriginDimensionSpacePoints(OriginDimensionSpacePointSet $affectedOriginDimensionSpacePoints): OriginDimensionSpacePointSet
    {
        $allowedOrderedDimensionSpacePoints = $this->interDimensionalVariationGraph->getDimensionSpacePoints();

        $orderedAffectedOriginDimensionSpacePoints = OriginDimensionSpacePointSet::fromDimensionSpacePointSet(
            $allowedOrderedDimensionSpacePoints->getIntersection(
                $affectedOriginDimensionSpacePoints->toDimensionSpacePointSet()
            )
        );

        if (!$orderedAffectedOriginDimensionSpacePoints->equals($affectedOriginDimensionSpacePoints)) {
            throw new DimensionSpacePointNotFound(
                sprintf('The dimension space points %s were not found in the allowed dimension subspace %s', $affectedOriginDimensionSpacePoints->getDifference($orderedAffectedOriginDimensionSpacePoints)->toJson(), $allowedOrderedDimensionSpacePoints->toJson()),
                1778587626
            );
        }
        return $orderedAffectedOriginDimensionSpacePoints;
    }
}
