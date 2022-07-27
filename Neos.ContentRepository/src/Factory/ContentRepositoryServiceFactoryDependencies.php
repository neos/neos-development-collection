<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Factory;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\EventStore\EventPersister;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\EventStore\EventStoreInterface;

/**
 * Implementation detail of {@see ContentRepositoryServiceFactoryInterface}
 */
final class ContentRepositoryServiceFactoryDependencies
{
    private function __construct(
        // These properties are from ProjectionFactoryDependencies
        public readonly ContentRepositoryIdentifier $contentRepositoryIdentifier,
        public readonly EventStoreInterface $eventStore,
        public readonly EventNormalizer $eventNormalizer,
        public readonly NodeTypeManager $nodeTypeManager,
        public readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        public readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        public readonly PropertyConverter $propertyConverter,

        public readonly ContentRepository $contentRepository,
        // we don't need Projections, because this is included in ContentRepository->getState()
        // we don't need CommandBus, because this is included in ContentRepository->handle()
        // I am unsure about ContentStreamRepository; but this is a kinda dirty class right now; so I don't want to expose it right now until we need it outside
        public readonly EventPersister $eventPersister,
    )
    {
    }

    public static function create(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        ContentRepository $contentRepository,
        EventPersister $eventPersister,
    ): self {
        return new self(
            $projectionFactoryDependencies->contentRepositoryIdentifier,
            $projectionFactoryDependencies->eventStore,
            $projectionFactoryDependencies->eventNormalizer,
            $projectionFactoryDependencies->nodeTypeManager,
            $projectionFactoryDependencies->contentDimensionZookeeper,
            $projectionFactoryDependencies->interDimensionalVariationGraph,
            $projectionFactoryDependencies->propertyConverter,
            $contentRepository,
            $eventPersister
        );
    }
}
