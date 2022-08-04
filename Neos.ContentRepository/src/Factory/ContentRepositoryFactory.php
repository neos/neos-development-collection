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

use Neos\ContentRepository\CommandHandler\CommandBus;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\EventStore\EventPersister;
use Neos\ContentRepository\Feature\ContentStreamCommandHandler;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\DimensionSpaceCommandHandler;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Projection\Projections;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\EventStore\EventStoreInterface;
use Symfony\Component\Serializer\Serializer;

final class ContentRepositoryFactory
{
    private ProjectionFactoryDependencies $projectionFactoryDependencies;
    private Projections $projections;

    public function __construct(
        ContentRepositoryIdentifier $contentRepositoryIdentifier,
        EventStoreInterface $eventStore,
        NodeTypeManager $nodeTypeManager,
        ContentDimensionSourceInterface $contentDimensionSource,
        Serializer $propertySerializer,
        ProjectionsFactory $projectionsFactory,
        private readonly ProjectionCatchUpTriggerInterface $projectionCatchUpTrigger
    ) {
        $contentDimensionZookeeper = new ContentDimensionZookeeper($contentDimensionSource);
        $interDimensionalVariationGraph = new InterDimensionalVariationGraph($contentDimensionSource, $contentDimensionZookeeper);

        $this->projectionFactoryDependencies = new ProjectionFactoryDependencies(
            $contentRepositoryIdentifier,
            $eventStore,
            new EventNormalizer(),
            $nodeTypeManager,
            $contentDimensionSource,
            $contentDimensionZookeeper,
            $interDimensionalVariationGraph,
            new PropertyConverter($propertySerializer)
        );

        $this->projections = $projectionsFactory->build($this->projectionFactoryDependencies);
    }

    // The following properties store "singleton" references of objects for this content repository
    private ?ContentRepository $contentRepository = null;
    private ?CommandBus $commandBus = null;
    private ?EventPersister $eventPersister = null;

    /**
     * Builds and returns the content repository. If it is already built, returns the same instance.
     *
     * @return ContentRepository
     */
    public function build(): ContentRepository
    {
        if (!$this->contentRepository) {
            $this->contentRepository = new ContentRepository(
                $this->buildCommandBus(),
                $this->projectionFactoryDependencies->eventStore,
                $this->projections,
                $this->buildEventPersister(),
                $this->projectionFactoryDependencies->nodeTypeManager
            );
        }
        return $this->contentRepository;
    }

    /**
     * A service is a high-level "application part" which builds upon the CR internals.
     *
     * You don't usually need this yourself, but it is usually enough to simply use the {@see ContentRepository}
     * instance. If you want to extend the CR core and need to hook deeply into CR internals, this is what the
     * {@see ContentRepositoryServiceInterface} is for.
     *
     * @template T of ContentRepositoryServiceInterface
     * @param ContentRepositoryServiceFactoryInterface<T> $serviceFactory
     * @return T
     */
    public function buildService(ContentRepositoryServiceFactoryInterface $serviceFactory): ContentRepositoryServiceInterface
    {
        $serviceFactoryDependencies = ContentRepositoryServiceFactoryDependencies::create(
            $this->projectionFactoryDependencies,
            $this->build(),
            $this->eventPersister
        );
        return $serviceFactory->build($serviceFactoryDependencies);
    }

    private function buildCommandBus(): CommandBus
    {
        if (!$this->commandBus) {
            $this->commandBus = new CommandBus(
                new ContentStreamCommandHandler(
                ),
                new WorkspaceCommandHandler(
                    $this->buildEventPersister(),
                    $this->projectionFactoryDependencies->eventStore,
                    $this->projectionFactoryDependencies->eventNormalizer
                ),
                new NodeAggregateCommandHandler(
                    $this->projectionFactoryDependencies->nodeTypeManager,
                    $this->projectionFactoryDependencies->contentDimensionZookeeper,
                    $this->projectionFactoryDependencies->interDimensionalVariationGraph,
                    $this->projectionFactoryDependencies->propertyConverter
                ),
                new DimensionSpaceCommandHandler(
                    $this->projectionFactoryDependencies->contentDimensionZookeeper,
                    $this->projectionFactoryDependencies->interDimensionalVariationGraph
                ),
                new NodeDuplicationCommandHandler(
                    $this->projectionFactoryDependencies->nodeTypeManager,
                    $this->projectionFactoryDependencies->contentDimensionZookeeper,
                    $this->projectionFactoryDependencies->interDimensionalVariationGraph
                )
            );
        }
        return $this->commandBus;
    }

    private function buildEventPersister(): EventPersister
    {
        if (!$this->eventPersister) {
            $this->eventPersister = new EventPersister(
                $this->projectionFactoryDependencies->eventStore,
                $this->projectionCatchUpTrigger,
                $this->projectionFactoryDependencies->eventNormalizer,
                $this->projections
            );
        }
        return $this->eventPersister;
    }
}
