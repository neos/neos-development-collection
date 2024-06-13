<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<StructureAdjustmentService>
 */
class StructureAdjustmentServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): StructureAdjustmentService
    {
        return new StructureAdjustmentService(
            $serviceFactoryDependencies->contentRepository,
            $serviceFactoryDependencies->eventPersister,
            $serviceFactoryDependencies->nodeTypeManager,
            $serviceFactoryDependencies->interDimensionalVariationGraph,
            $serviceFactoryDependencies->propertyConverter,
        );
    }
}
