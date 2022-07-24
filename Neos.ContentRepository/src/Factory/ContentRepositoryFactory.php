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


use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\CommandHandler\CommandBus;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\Feature\ContentStreamCommandHandler;
use Neos\ContentRepository\Feature\ContentStreamRepository;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\DimensionSpaceCommandHandler;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Projection\Changes\ChangeProjection;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Content\ContentGraphProjection;
use Neos\ContentRepository\Projection\ContentStream\ContentStreamProjection;
use Neos\ContentRepository\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Projection\Projections;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Projection\Workspace\WorkspaceProjection;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\EventStore\CatchUp\CheckpointStorageInterface;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Log\ThrowableStorageInterface;
use Symfony\Component\Serializer\Serializer;

final class ContentRepositoryFactory
{

    private function __construct(
        private readonly DbalClientInterface $dbalClient,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper, // TODO: check whether this is actually specified from outside
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph, // TODO: check whether this is actually specified from outside
        private readonly ThrowableStorageInterface $throwableStorage, // TODO
        private readonly Serializer $propertySerializer,
        private readonly ProjectionCatchUpTriggerInterface $projectionCatchUpTrigger, // TODO implement
        private readonly string $tableNamePrefix,
    )
    {
    }

    // The following properties store "singleton" references of objects for this content repository
    private ?ContentRepository $contentRepository = null;
    private ?CommandBus $commandBus = null;
    private ?EventStoreInterface $eventStore = null;
    private ?ContentStreamRepository $contentStreamRepository = null;
    private ?ReadSideMemoryCacheManager $readSideMemoryCacheManager = null;
    private ?PropertyConverter $propertyConverter = null;
    private ?EventNormalizer $eventNormalizer = null;
    private ?Projections $projections = null;

    public function build(): ContentRepository
    {
        if (!$this->contentRepository) {
            $this->contentRepository = new ContentRepository(
                $this->buildCommandBus(),
                $this->buildEventStore(),
                $this->buildProjections(),
                $this->buildEventNormalizer(),
                $this->projectionCatchUpTrigger,
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
                ),
                new NodeAggregateCommandHandler(
                    $this->buildContentStreamRepository(),
                    $this->nodeTypeManager,
                    $this->contentDimensionZookeeper,
                    $this->interDimensionalVariationGraph,
                    $this->buildReadSideMemoryCacheManager(),
                    $this->buildPropertyConverter()
                ),
                new DimensionSpaceCommandHandler(
                    $this->buildReadSideMemoryCacheManager(),
                    $this->contentDimensionZookeeper,
                    $this->interDimensionalVariationGraph
                ),
                new NodeDuplicationCommandHandler(
                    $this->buildContentStreamRepository(),
                    $this->nodeTypeManager,
                    $this->buildReadSideMemoryCacheManager(),
                    $this->contentDimensionZookeeper,
                    $this->interDimensionalVariationGraph
                )
            );
        }
        return $this->commandBus;
    }

    private function buildContentStreamRepository(): ContentStreamRepository
    {
        if (!$this->contentStreamRepository) {
            $this->contentStreamRepository = new ContentStreamRepository(
                $this->buildEventStore()
            );
        }
        return $this->contentStreamRepository;
    }

    private function buildReadSideMemoryCacheManager(): ReadSideMemoryCacheManager
    {
        if (!$this->readSideMemoryCacheManager) {
            $projections = $this->buildProjections();

            $this->readSideMemoryCacheManager = new ReadSideMemoryCacheManager(
                $projections->get(ContentGraphProjection::class)->getState(),
                $projections->get(WorkspaceProjection::class)->getState()
            );
        }
        return $this->readSideMemoryCacheManager;
    }

    private function buildPropertyConverter(): PropertyConverter
    {
        if (!$this->propertyConverter) {
            $this->propertyConverter = new PropertyConverter(
                $this->propertySerializer
            );
        }
        return $this->propertyConverter;
    }

    private function buildEventStore(): EventStoreInterface
    {
        if (!$this->eventStore) {
            $this->eventStore = new DoctrineEventStore(
                $this->dbalClient->getConnection(),
                $this->tableNamePrefix . '_eventstore' // TODO: Naming conventions for projections vs event store? (p_ prefix?)
            );
        }
        return $this->eventStore;
    }

    private function buildProjections(): Projections
    {
        // TODO: PROJECTIONS
        if (!$this->projections) {
            $projections = Projections::create();
            $projections = $projections->with(
                new ContentGraphProjection(
                // TODO: dependent on doctrine or postgres
                    new DoctrineDbalContentGraphProjection(
                        $this->buildEventNormalizer(),
                        $this->buildCheckpointStorage('DoctrineDbalContentGraphProjection'),
                        $this->dbalClient,
                        new NodeFactory( // TODO: No singleton (but here not needed). Is this a problem?
                            $this->nodeTypeManager,
                            $this->buildPropertyConverter()
                        ),
                        new ProjectionContentGraph(
                            $this->dbalClient,
                            $this->tableNamePrefix
                        ),
                        $this->throwableStorage,
                        $this->tableNamePrefix
                    )
                )
            );
            $projections = $projections->with(
                new ContentStreamProjection(
                    $this->buildEventNormalizer(),
                    $this->buildCheckpointStorage('ContentStreamProjection'),
                    $this->dbalClient,
                    $this->tableNamePrefix
                )
            );
            $projections = $projections->with(
                new WorkspaceProjection(
                    $this->buildEventNormalizer(),
                    $this->buildCheckpointStorage('WorkspaceProjection'),
                    $this->dbalClient,
                    $this->tableNamePrefix
                )
            );

            $workspaceFinder = $projections->get(WorkspaceProjection::class)->getState();
            assert($workspaceFinder instanceof WorkspaceFinder);
            $projections = $projections->with(
                new ChangeProjection(
                    $this->buildEventNormalizer(),
                    $this->buildCheckpointStorage('ChangeProjection'),
                    $this->dbalClient,
                    $workspaceFinder,
                    $this->tableNamePrefix
                )
            );

            $this->projections = $projections;
        }

        // TODO: HOW TO BUILD OTHER PROJECTIONS HERE (not part of ES CR Core), which still need stuff like the SAME EventNormalizer?

        return $this->projections;
    }

    private function buildEventNormalizer(): EventNormalizer
    {
        if (!$this->eventNormalizer) {
            $this->eventNormalizer = new EventNormalizer();
        }
        return $this->eventNormalizer;
    }

    private function buildCheckpointStorage(string $subscriberId): CheckpointStorageInterface
    {
        return new DoctrineCheckpointStorage(
            $this->dbalClient->getConnection(),
            $this->tableNamePrefix . 'checkpoint',
            $subscriberId
        );
    }
}
