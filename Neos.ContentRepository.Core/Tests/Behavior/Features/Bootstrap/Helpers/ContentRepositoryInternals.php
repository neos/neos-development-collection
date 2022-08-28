<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Helpers;


use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\EventPersister;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Factory\ContentRepositoryIdentifier;
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
