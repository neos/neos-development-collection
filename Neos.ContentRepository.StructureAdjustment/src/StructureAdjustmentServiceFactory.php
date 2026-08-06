<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Infrastructure\Property\NodeTypeDefaultPropertySerializer;

/**
 * @implements ContentRepositoryServiceFactoryInterface<StructureAdjustmentService>
 */
class StructureAdjustmentServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): StructureAdjustmentService
    {
        return new StructureAdjustmentService(
            $serviceFactoryDependencies->contentRepository,
            $serviceFactoryDependencies->eventStore,
            $serviceFactoryDependencies->eventNormalizer,
            $serviceFactoryDependencies->subscriptionEngine,
            $serviceFactoryDependencies->nodeTypeManager,
            $serviceFactoryDependencies->interDimensionalVariationGraph,
            new NodeTypeDefaultPropertySerializer(
                $serviceFactoryDependencies->propertyConverter,
                $serviceFactoryDependencies->clock,
            ),
        );
    }
}
