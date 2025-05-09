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
use Neos\ContentRepository\Core\CommandHandler\CommandHandlingDependencies;
use Neos\ContentRepository\Core\CommandHandler\CommandSimulatorFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\DimensionSpaceCommandHandler;
use Neos\ContentRepository\Core\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Core\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryDependencies;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionFactoryInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStates;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionStoreInterface;
use Neos\ContentRepository\Core\Subscription\Subscriber\ProjectionSubscriber;
use Neos\ContentRepository\Core\Subscription\Subscriber\Subscribers;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepositoryRegistry\Factory\AuthProvider\AuthProviderFactoryInterface;
use Neos\EventStore\EventStoreInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Main factory to build a {@see ContentRepository} object.
 *
 * @api
 */
final class ContentRepositoryFactory
{
    private SubscriptionEngine $subscriptionEngine;
    private ContentGraphProjectionInterface $contentGraphProjection;
    private ProjectionStates $additionalProjectionStates;
    private EventNormalizer $eventNormalizer;
    private ContentDimensionZookeeper $contentDimensionZookeeper;
    private InterDimensionalVariationGraph $interDimensionalVariationGraph;
    private PropertyConverter $propertyConverter;

    // guards against recursion and memory overflow
    private bool $isBuilding = false;

    // The "singleton" reference for this content repository
    private ?ContentRepository $contentRepositoryRuntimeCache = null;

    /**
     * @param CatchUpHookFactoryInterface<ContentGraphReadModelInterface>|null $contentGraphCatchUpHookFactory
     */
    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly EventStoreInterface $eventStore,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly ContentDimensionSourceInterface $contentDimensionSource,
        Serializer $propertySerializer,
        private readonly AuthProviderFactoryInterface $authProviderFactory,
        private readonly ClockInterface $clock,
        SubscriptionStoreInterface $subscriptionStore,
        ContentGraphProjectionFactoryInterface $contentGraphProjectionFactory,
        private readonly CatchUpHookFactoryInterface|null $contentGraphCatchUpHookFactory,
        private readonly CommandHooksFactory $commandHooksFactory,
        private readonly ContentRepositorySubscriberFactories $additionalSubscriberFactories,
        LoggerInterface|null $logger = null,
    ) {
        $this->contentDimensionZookeeper = new ContentDimensionZookeeper($contentDimensionSource);
        $this->interDimensionalVariationGraph = new InterDimensionalVariationGraph(
            $contentDimensionSource,
            $this->contentDimensionZookeeper
        );
        $this->eventNormalizer = EventNormalizer::create();
        $this->propertyConverter = new PropertyConverter($propertySerializer);
        $subscriberFactoryDependencies = SubscriberFactoryDependencies::create(
            $contentRepositoryId,
            $nodeTypeManager,
            $contentDimensionSource,
            $this->interDimensionalVariationGraph,
            $this->propertyConverter,
        );
        $subscribers = [];
        $additionalProjectionStates = [];
        foreach ($this->additionalSubscriberFactories as $additionalSubscriberFactory) {
            $subscriber = $additionalSubscriberFactory->build($subscriberFactoryDependencies);
            $additionalProjectionStates[] = $subscriber->projection->getState();
            $subscribers[] = $subscriber;
        }
        $this->additionalProjectionStates = ProjectionStates::fromArray($additionalProjectionStates);
        $this->contentGraphProjection = $contentGraphProjectionFactory->build($subscriberFactoryDependencies);
        $subscribers[] = $this->buildContentGraphSubscriber();
        $this->subscriptionEngine = new SubscriptionEngine($this->eventStore, $subscriptionStore, Subscribers::fromArray($subscribers), $this->eventNormalizer, $logger);
    }

    private function buildContentGraphSubscriber(): ProjectionSubscriber
    {
        return new ProjectionSubscriber(
            SubscriptionId::fromString('contentGraph'),
            $this->contentGraphProjection,
            $this->contentGraphCatchUpHookFactory?->build(CatchUpHookFactoryDependencies::create(
                $this->contentRepositoryId,
                $this->contentGraphProjection->getState(),
                $this->nodeTypeManager,
                $this->contentDimensionSource,
                $this->interDimensionalVariationGraph,
            )),
        );
    }

    /**
     * Builds and returns the content repository. If it is already built, returns the same instance.
     *
     * @return ContentRepository
     * @api
     */
    public function getOrBuild(): ContentRepository
    {
        if ($this->contentRepositoryRuntimeCache) {
            return $this->contentRepositoryRuntimeCache;
        }
        if ($this->isBuilding) {
            throw new \RuntimeException(sprintf('Content repository "%s" was attempted to be build in recursion.', $this->contentRepositoryId->value), 1730552199);
        }
        $this->isBuilding = true;

        $contentGraphReadModel = $this->contentGraphProjection->getState();
        $commandHandlingDependencies = new CommandHandlingDependencies($contentGraphReadModel);

        // we dont need full recursion in rebase - e.g apply workspace commands - and thus we can use this set for simulation
        $commandBusForRebaseableCommands = new CommandBus(
            $commandHandlingDependencies,
            new NodeAggregateCommandHandler(
                $this->nodeTypeManager,
                $this->contentDimensionZookeeper,
                $this->interDimensionalVariationGraph,
                $this->propertyConverter,
            ),
            new DimensionSpaceCommandHandler(
                $this->interDimensionalVariationGraph,
                $this->nodeTypeManager,
            )
        );

        $commandSimulatorFactory = new CommandSimulatorFactory(
            $this->contentGraphProjection,
            $this->eventNormalizer,
            $commandBusForRebaseableCommands
        );

        $publicCommandBus = $commandBusForRebaseableCommands->withAdditionalHandlers(
            new WorkspaceCommandHandler(
                $commandSimulatorFactory,
                $this->eventStore,
                $this->eventNormalizer,
            )
        );
        $authProvider = $this->authProviderFactory->build($this->contentRepositoryId, $contentGraphReadModel);
        $commandHooks = $this->commandHooksFactory->build(CommandHooksFactoryDependencies::create(
            $this->contentRepositoryId,
            $this->contentGraphProjection->getState(),
            $this->nodeTypeManager,
            $this->contentDimensionSource,
            $this->interDimensionalVariationGraph,
        ));
        $this->contentRepositoryRuntimeCache = new ContentRepository(
            $this->contentRepositoryId,
            $publicCommandBus,
            $this->eventStore,
            $this->eventNormalizer,
            $this->subscriptionEngine,
            $this->nodeTypeManager,
            $this->interDimensionalVariationGraph,
            $this->contentDimensionSource,
            $authProvider,
            $this->clock,
            $contentGraphReadModel,
            $commandHooks,
            $this->additionalProjectionStates,
        );
        $this->isBuilding = false;
        return $this->contentRepositoryRuntimeCache;
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
            $this->contentRepositoryId,
            $this->eventStore,
            $this->eventNormalizer,
            $this->nodeTypeManager,
            $this->contentDimensionSource,
            $this->contentDimensionZookeeper,
            $this->interDimensionalVariationGraph,
            $this->propertyConverter,
            $this->getOrBuild(),
            $this->contentGraphProjection->getState(),
            $this->subscriptionEngine,
        );
        return $serviceFactory->build($serviceFactoryDependencies);
    }
}
