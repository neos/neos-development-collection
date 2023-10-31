<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<StructureAdjustmentService>
 */
class StructureAdjustmentServiceFactory implements ContentRepositoryServiceFactoryInterface
{

    /**
     * @param ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
     * @return ContentRepositoryServiceInterface
     */
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): StructureAdjustmentService
    {
        return new StructureAdjustmentService(
            $serviceFactoryDependencies->contentRepository,
            $serviceFactoryDependencies->eventPersister,
            $serviceFactoryDependencies->nodeTypeManager,
            $serviceFactoryDependencies->interDimensionalVariationGraph,
        );
    }
}
