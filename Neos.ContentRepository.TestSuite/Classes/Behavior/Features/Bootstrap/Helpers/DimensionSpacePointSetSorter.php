<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;

/**
 * Sort points in given set
 */
class DimensionSpacePointSetSorter
{
    public static function sortSet(DimensionSpacePointSet|string $set): DimensionSpacePointSet
    {
        $dimensionSpacePointSet = is_string($set) ? DimensionSpacePointSet::fromJsonString($set) : $set;
        $points = $dimensionSpacePointSet->points;
        ksort($points);

        return DimensionSpacePointSet::fromArray($points);
    }
    public static function sortOriginSet(OriginDimensionSpacePointSet|string $set): OriginDimensionSpacePointSet
    {
        $dimensionSpacePointSet = is_string($set) ? OriginDimensionSpacePointSet::fromJsonString($set) : $set;
        $points = iterator_to_array($dimensionSpacePointSet);
        ksort($points);

        return new OriginDimensionSpacePointSet($points);
    }
}
