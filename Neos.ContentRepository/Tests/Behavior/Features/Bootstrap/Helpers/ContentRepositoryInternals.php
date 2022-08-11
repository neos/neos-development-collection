<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Helpers;


use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\EventStore\EventPersister;
use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\EventStore\EventStoreInterface;

class ContentRepositoryInternals implements ContentRepositoryServiceInterface
{
    public function __construct(
        // These properties are from ProjectionFactoryDependencies
        public readonly ContentRepositoryIdentifier $contentRepositoryIdentifier,
        public readonly EventStoreInterface $eventStore,
        public readonly EventNormalizer $eventNormalizer,
        public readonly NodeTypeManager $nodeTypeManager,
        public readonly ContentDimensionSourceInterface $contentDimensionSource,
        public readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        public readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        public readonly PropertyConverter $propertyConverter,

        public readonly ContentRepository $contentRepository,
        // we don't need Projections, because this is included in ContentRepository->getState()
        // we don't need CommandBus, because this is included in ContentRepository->handle()
        public readonly EventPersister $eventPersister,
    )
    {
    }

}
