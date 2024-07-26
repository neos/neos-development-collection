<?php

declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;

final class DimensionSpacePointFactory
{
    /**
     * @param array<string,array<int,string>> $legacyDimensionArray
     */
    public static function tryCreateFromLegacyArray(
        array $legacyDimensionArray,
        ContentDimensionSourceInterface $contentDimensionSource
    ): ?DimensionSpacePoint {
        $coordinates = [];
        foreach ($contentDimensionSource->getContentDimensionsOrderedByPriority() as $contentDimensionIdentifier => $contentDimension) {
            if (array_key_exists($contentDimensionIdentifier, $legacyDimensionArray)) {
                return null;
            }
            $coordinates[$contentDimensionIdentifier] = reset($legacyDimensionArray[$contentDimensionIdentifier]);
        }

        return DimensionSpacePoint::fromArray($coordinates);
    }
}
