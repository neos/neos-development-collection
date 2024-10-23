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
use Neos\ContentRepository\Core\Projection\ProjectionsAndCatchUpHooks;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\EventStore\EventStoreInterface;

/**
 * Implementation detail of {@see ContentRepositoryServiceFactoryInterface}
 *
 * @internal as dependency collection inside {@see ContentRepositoryServiceFactoryInterface}
 */
final readonly class ContentRepositoryServiceFactoryDependencies
{
    private function __construct(
        // These properties are from ProjectionFactoryDependencies
        public ContentRepositoryId $contentRepositoryId,
        public EventStoreInterface $eventStore,
        public EventNormalizer $eventNormalizer,
        public NodeTypeManager $nodeTypeManager,
        public ContentDimensionSourceInterface $contentDimensionSource,
        public ContentDimensionZookeeper $contentDimensionZookeeper,
        public InterDimensionalVariationGraph $interDimensionalVariationGraph,
        public PropertyConverter $propertyConverter,
        public ContentRepository $contentRepository,
        // we don't need CommandBus, because this is included in ContentRepository->handle()
        public EventPersister $eventPersister,
        public ProjectionsAndCatchUpHooks $projectionsAndCatchUpHooks,
    ) {
    }

    /**
     * @internal
     */
    public static function create(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        ContentRepository $contentRepository,
        EventPersister $eventPersister,
        ProjectionsAndCatchUpHooks $projectionsAndCatchUpHooks,
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
            $projectionsAndCatchUpHooks,
        );
    }
}
