<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;

/**
 * @deprecated really un-nice :D
 */
class DimensionsMenuItemsImplementationInternals implements ContentRepositoryServiceInterface
{
    public function __construct(
        public readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        public readonly ContentDimensionSourceInterface $contentDimensionSource,
        public readonly InterDimensionalVariationGraph $interDimensionalVariationGraph
    ) {
    }
}
