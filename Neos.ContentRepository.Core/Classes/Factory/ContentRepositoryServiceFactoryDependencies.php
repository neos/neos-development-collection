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

namespace Neos\ContentRepository\Core\Factory;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\EventPersister;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\EventStore\EventStoreInterface;

/**
 * Implementation detail of {@see ContentRepositoryServiceFactoryInterface}
 *
 * @api because you get it as argument inside {@see ContentRepositoryServiceFactoryInterface}
 */
final class ContentRepositoryServiceFactoryDependencies
{
    private function __construct(
        // These properties are from ProjectionFactoryDependencies
        public readonly ContentRepositoryId $contentRepositoryId,
        public readonly EventStoreInterface $eventStore,
        public readonly EventNormalizer $eventNormalizer,
        public readonly NodeTypeManager $nodeTypeManager,
        public readonly ContentDimensionSourceInterface $contentDimensionSource,
        public readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        public readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        public readonly PropertyConverter $propertyConverter,
        public readonly ContentRepository $contentRepository,
        // we don't need CommandBus, because this is included in ContentRepository->handle()
        public readonly EventPersister $eventPersister,
        public readonly Projections $projections,
    ) {
    }

    /**
     * @internal
     */
    public static function create(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        ContentRepository $contentRepository,
        EventPersister $eventPersister,
        Projections $projections,
    ): self {
        return new self(
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->eventStore,
            $projectionFactoryDependencies->eventNormalizer,
            $projectionFactoryDependencies->nodeTypeManager,
            $projectionFactoryDependencies->contentDimensionSource,
            $projectionFactoryDependencies->contentDimensionZookeeper,
            $projectionFactoryDependencies->interDimensionalVariationGraph,
            $projectionFactoryDependencies->propertyConverter,
            $contentRepository,
            $eventPersister,
            $projections,
        );
    }
}
