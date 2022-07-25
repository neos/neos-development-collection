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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\EventStore\EventPersister;
use Neos\ContentRepository\Feature\ContentStreamCommandHandler;
use Neos\ContentRepository\Feature\ContentStreamRepository;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\DimensionSpaceCommandHandler;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Projection\Content\ContentGraphProjection;
use Neos\ContentRepository\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Projection\Projections;
use Neos\ContentRepository\Projection\Workspace\WorkspaceProjection;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Log\ThrowableStorageInterface;
use Symfony\Component\Serializer\Serializer;

final class ContentRepositoryFactory
{

    private ProjectionFactoryDependencies $projectionFactoryDependencies;
    private Projections $projections;

    private function __construct(
        ContentRepositoryIdentifier $contentRepositoryIdentifier,
        EventStoreInterface $eventStore,
        NodeTypeManager $nodeTypeManager,
        ContentDimensionZookeeper $contentDimensionZookeeper, // TODO: check whether this is actually specified from outside
        InterDimensionalVariationGraph $interDimensionalVariationGraph, // TODO: check whether this is actually specified from outside
        ThrowableStorageInterface $throwableStorage, // TODO
        Serializer $propertySerializer,
        ProjectionsFactory $projectionsFactory,
        private readonly ProjectionCatchUpTriggerInterface $projectionCatchUpTrigger // TODO implement

    )
    {
        $this->projectionFactoryDependencies = new ProjectionFactoryDependencies(
            $contentRepositoryIdentifier,
            $eventStore,
            new EventNormalizer(),
            $nodeTypeManager,
            $contentDimensionZookeeper,
            $interDimensionalVariationGraph,
            $throwableStorage,
            new PropertyConverter($propertySerializer)
        );

        $this->projections = $projectionsFactory->build($this->projectionFactoryDependencies);
    }

    // The following properties store "singleton" references of objects for this content repository
    private ?ContentRepository $contentRepository = null;
    private ?CommandBus $commandBus = null;
    private ?ContentStreamRepository $contentStreamRepository = null;
    private ?ReadSideMemoryCacheManager $readSideMemoryCacheManager = null;
    private ?EventPersister $eventPersister = null;

    public function build(): ContentRepository
    {
        if (!$this->contentRepository) {
            $this->contentRepository = new ContentRepository(
                $this->buildCommandBus(),
                $this->projectionFactoryDependencies->eventStore,
                $this->projections,
                $this->buildEventPersister(),
            );
        }
        return $this->contentRepository;
    }

    private function buildCommandBus(): CommandBus
    {
        if (!$this->commandBus) {
            $this->commandBus = new CommandBus(
                new ContentStreamCommandHandler(
                    $this->buildContentStreamRepository(),
                    $this->buildReadSideMemoryCacheManager(),
                ),
                new WorkspaceCommandHandler(
                    $this->buildReadSideMemoryCacheManager(),
                    $this->buildEventPersister(),
                    $this->projectionFactoryDependencies->eventStore,
                    $this->projectionFactoryDependencies->eventNormalizer
                ),
                new NodeAggregateCommandHandler(
                    $this->buildContentStreamRepository(),
                    $this->projectionFactoryDependencies->nodeTypeManager,
                    $this->projectionFactoryDependencies->contentDimensionZookeeper,
                    $this->projectionFactoryDependencies->interDimensionalVariationGraph,
                    $this->buildReadSideMemoryCacheManager(),
                    $this->projectionFactoryDependencies->propertyConverter
                ),
                new DimensionSpaceCommandHandler(
                    $this->buildReadSideMemoryCacheManager(),
                    $this->projectionFactoryDependencies->contentDimensionZookeeper,
                    $this->projectionFactoryDependencies->interDimensionalVariationGraph
                ),
                new NodeDuplicationCommandHandler(
                    $this->buildContentStreamRepository(),
                    $this->projectionFactoryDependencies->nodeTypeManager,
                    $this->buildReadSideMemoryCacheManager(),
                    $this->projectionFactoryDependencies->contentDimensionZookeeper,
                    $this->projectionFactoryDependencies->interDimensionalVariationGraph
                )
            );
        }
        return $this->commandBus;
    }

    private function buildContentStreamRepository(): ContentStreamRepository
    {
        if (!$this->contentStreamRepository) {
            $this->contentStreamRepository = new ContentStreamRepository(
                $this->projectionFactoryDependencies->eventStore
            );
        }
        return $this->contentStreamRepository;
    }

    private function buildReadSideMemoryCacheManager(): ReadSideMemoryCacheManager
    {
        if (!$this->readSideMemoryCacheManager) {

            $this->readSideMemoryCacheManager = new ReadSideMemoryCacheManager(
                $this->projections->get(ContentGraphProjection::class)->getState(),
                $this->projections->get(WorkspaceProjection::class)->getState()
            );
        }
        return $this->readSideMemoryCacheManager;
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
