<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Helpers;


use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;

class ContentRepositoryInternalsFactory implements ContentRepositoryServiceFactoryInterface
{

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
    {
        return new ContentRepositoryInternals(
            $serviceFactoryDependencies->contentRepositoryIdentifier,
            $serviceFactoryDependencies->eventStore,
            $serviceFactoryDependencies->eventNormalizer,
            $serviceFactoryDependencies->nodeTypeManager,
            $serviceFactoryDependencies->contentDimensionSource,
            $serviceFactoryDependencies->contentDimensionZookeeper,
            $serviceFactoryDependencies->interDimensionalVariationGraph,
            $serviceFactoryDependencies->propertyConverter,
            $serviceFactoryDependencies->contentRepository,
            $serviceFactoryDependencies->eventPersister,
        );
    }
}
