<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;

/**
 * @deprecated really un-nice :D
 */
class DimensionsMenuItemsImplementationInternals implements ContentRepositoryServiceInterface
{
    public function __construct(
        public readonly ContentDimensionSourceInterface $contentDimensionSource,
        public readonly InterDimensionalVariationGraph $interDimensionalVariationGraph
    ) {
    }
}
