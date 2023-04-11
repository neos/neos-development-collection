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

use Neos\ContentRepository\Core\CommandHandler\CommandBus;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\EventPersister;
use Neos\ContentRepository\Core\Feature\ContentStreamCommandHandler;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\DimensionSpaceCommandHandler;
use Neos\ContentRepository\Core\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Core\Feature\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\ContentRepository\Core\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\EventStore\EventStoreInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Main factory to build a {@see ContentRepository} object.
 *
 * @api
 */
final class ContentRepositoryFactory
{
    private ProjectionFactoryDependencies $projectionFactoryDependencies;
    private Projections $projections;

    public function __construct(
        ContentRepositoryId $contentRepositoryId,
        EventStoreInterface $eventStore,
        NodeTypeManager $nodeTypeManager,
        ContentDimensionSourceInterface $contentDimensionSource,
        Serializer $propertySerializer,
        ProjectionsFactory $projectionsFactory,
        private readonly ProjectionCatchUpTriggerInterface $projectionCatchUpTrigger,
        private readonly UserIdProviderInterface $userIdProvider,
        private readonly ClockInterface $clock,
    ) {
        $contentDimensionZookeeper = new ContentDimensionZookeeper($contentDimensionSource);
        $interDimensionalVariationGraph = new InterDimensionalVariationGraph(
            $contentDimensionSource,
            $contentDimensionZookeeper
        );

        $this->projectionFactoryDependencies = new ProjectionFactoryDependencies(
            $contentRepositoryId,
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
     * @api
     */
    public function build(): ContentRepository
    {
        if (!$this->contentRepository) {
            $this->contentRepository = new ContentRepository(
                $this->buildCommandBus(),
                $this->projectionFactoryDependencies->eventStore,
                $this->projections,
                $this->buildEventPersister(),
                $this->projectionFactoryDependencies->nodeTypeManager,
                $this->projectionFactoryDependencies->interDimensionalVariationGraph,
                $this->projectionFactoryDependencies->contentDimensionSource,
                $this->userIdProvider,
                $this->clock,
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
    public function buildService(
        ContentRepositoryServiceFactoryInterface $serviceFactory
    ): ContentRepositoryServiceInterface {
        $serviceFactoryDependencies = ContentRepositoryServiceFactoryDependencies::create(
            $this->projectionFactoryDependencies,
            $this->build(),
            $this->buildEventPersister(),
            $this->projections,
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
