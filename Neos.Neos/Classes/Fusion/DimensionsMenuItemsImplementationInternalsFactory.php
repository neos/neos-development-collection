<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<DimensionsMenuItemsImplementationInternals>
 */
class DimensionsMenuItemsImplementationInternalsFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): DimensionsMenuItemsImplementationInternals {
        return new DimensionsMenuItemsImplementationInternals(
            $serviceFactoryDependencies->contentDimensionZookeeper,
            $serviceFactoryDependencies->contentDimensionSource,
            $serviceFactoryDependencies->interDimensionalVariationGraph
        );
    }
}
