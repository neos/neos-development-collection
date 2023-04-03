<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryFactory;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Factory\ProjectionsFactory;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepositoryRegistry\Exception\ContentRepositoryNotFound;
use Neos\ContentRepositoryRegistry\Exception\InvalidConfigurationException;
use Neos\ContentRepositoryRegistry\Factory\Clock\ClockFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource\ContentDimensionSourceFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\EventStore\EventStoreFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\NodeTypeManager\NodeTypeManagerFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\ProjectionCatchUpTriggerFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\UserIdProvider\UserIdProviderFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\PositionalArraySorter;
use Psr\Clock\ClockInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @api
 */
#[Flow\Scope("singleton")]
final class ContentRepositoryRegistry
{
    /**
     * @var array<string, ContentRepository>
     */
    private array $contentRepositoryInstances = [];

    /**
     * @var array<string, array<string, ContentRepositoryServiceInterface>>
     */
    private array $contentRepositoryServiceInstances = [];

    /**
     * @var array<string, ContentRepositoryFactory>
     */
    private array $factoryInstances = [];

    /**
     * @param array<mixed> $settings
     */
    public function __construct(
        private readonly array $settings,
        private readonly ObjectManagerInterface $objectManager
    )
    {
    }

    /**
     * @throws ContentRepositoryNotFound | InvalidConfigurationException
     */
    public function get(ContentRepositoryId $contentRepositoryId): ContentRepository
    {
        if (!array_key_exists($contentRepositoryId->value, $this->contentRepositoryInstances)) {
            $this->contentRepositoryInstances[$contentRepositoryId->value] = $this->getFactory($contentRepositoryId)->build();
        }
        return $this->contentRepositoryInstances[$contentRepositoryId->value];
    }

    public function subgraphForNode(Node $node): ContentSubgraphInterface
    {
        $contentRepository = $this->get($node->subgraphIdentity->contentRepositoryId);
        return $contentRepository->getContentGraph()->getSubgraph(
            $node->subgraphIdentity->contentStreamId,
            $node->subgraphIdentity->dimensionSpacePoint,
            $node->subgraphIdentity->visibilityConstraints
        );
    }

    /**
     * @param ContentRepositoryId $contentRepositoryId
     * @param ContentRepositoryServiceFactoryInterface<T> $contentRepositoryServiceFactory
     * @return T
     * @throws ContentRepositoryNotFound | InvalidConfigurationException
     * @template T of ContentRepositoryServiceInterface
     */
    public function getService(ContentRepositoryId $contentRepositoryId, ContentRepositoryServiceFactoryInterface $contentRepositoryServiceFactory): ContentRepositoryServiceInterface
    {
        if (!isset($this->contentRepositoryServiceInstances[$contentRepositoryId->value][get_class($contentRepositoryServiceFactory)])) {
            $this->contentRepositoryServiceInstances[$contentRepositoryId->value][get_class($contentRepositoryServiceFactory)] = $this->getFactory($contentRepositoryId)->buildService($contentRepositoryServiceFactory);
        }
        return $this->contentRepositoryServiceInstances[$contentRepositoryId->value][get_class($contentRepositoryServiceFactory)];
    }

    /**
     * @throws ContentRepositoryNotFound | InvalidConfigurationException
     */
    private function getFactory(ContentRepositoryId $contentRepositoryId): ContentRepositoryFactory
    {
        // This cache is CRUCIAL, because it ensures that the same CR always deals with the same objects internally, even if multiple services
        // are called on the same CR.
        if (!array_key_exists($contentRepositoryId->value, $this->factoryInstances)) {
            $this->factoryInstances[$contentRepositoryId->value] = $this->buildFactory($contentRepositoryId);
        }
        return $this->factoryInstances[$contentRepositoryId->value];
    }

    /**
     * @throws ContentRepositoryNotFound | InvalidConfigurationException
     */
    private function buildFactory(ContentRepositoryId $contentRepositoryId): ContentRepositoryFactory
    {
        assert(is_array($this->settings['contentRepositories']));
        assert(isset($this->settings['contentRepositories'][$contentRepositoryId->value]) && is_array($this->settings['contentRepositories'][$contentRepositoryId->value]), ContentRepositoryNotFound::notConfigured($contentRepositoryId));
        $contentRepositorySettings = $this->settings['contentRepositories'][$contentRepositoryId->value];
        assert(is_string($contentRepositorySettings['preset']));

        assert(isset($this->settings['presets']) && is_array($this->settings['presets']), InvalidConfigurationException::fromMessage('Content repository settings "%s" refer to a preset "%s", but there are not presets configured', $contentRepositoryId->value, $contentRepositorySettings['preset']));
        assert(isset($this->settings['presets'][$contentRepositorySettings['preset']]) && is_array($this->settings['presets'][$contentRepositorySettings['preset']]), InvalidConfigurationException::missingPreset($contentRepositoryId, $contentRepositorySettings['preset']));
        $contentRepositoryPreset = $this->settings['presets'][$contentRepositorySettings['preset']];
        try {
            $clock = $this->buildClock($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset);
            return new ContentRepositoryFactory(
                $contentRepositoryId,
                $this->buildEventStore($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset, $clock),
                $this->buildNodeTypeManager($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset),
                $this->buildContentDimensionSource($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset),
                $this->buildPropertySerializer($contentRepositorySettings, $contentRepositoryPreset),
                $this->buildProjectionsFactory($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset),
                $this->buildProjectionCatchUpTrigger($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset),
                $this->buildUserIdProvider($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset),
                $clock,
            );
        } catch (\Exception $exception) {
            throw InvalidConfigurationException::fromException($contentRepositoryId, $exception);
        }
    }

    private function buildEventStore(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $contentRepositoryPreset, ClockInterface $clock): EventStoreInterface
    {
        assert(isset($contentRepositoryPreset['eventStore']['factoryObjectName']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have eventStore.factoryObjectName configured.', $contentRepositorySettings['preset']));
        $eventStoreFactory = $this->objectManager->get($contentRepositoryPreset['eventStore']['factoryObjectName']);
        if (!$eventStoreFactory instanceof EventStoreFactoryInterface) {
            throw new \RuntimeException(sprintf('eventStore.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryId->value, EventStoreFactoryInterface::class, get_debug_type($eventStoreFactory)));
        }
        return $eventStoreFactory->build($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset['eventStore'], $clock);
    }

    private function buildNodeTypeManager(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $contentRepositoryPreset): NodeTypeManager
    {
        assert(isset($contentRepositoryPreset['nodeTypeManager']['factoryObjectName']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have nodeTypeManager.factoryObjectName configured.', $contentRepositorySettings['preset']));
        $nodeTypeManagerFactory = $this->objectManager->get($contentRepositoryPreset['nodeTypeManager']['factoryObjectName']);
        if (!$nodeTypeManagerFactory instanceof NodeTypeManagerFactoryInterface) {
            throw new \RuntimeException(sprintf('nodeTypeManager.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryId->value, NodeTypeManagerFactoryInterface::class, get_debug_type($nodeTypeManagerFactory)));
        }
        return $nodeTypeManagerFactory->build($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset['nodeTypeManager']);
    }

    private function buildContentDimensionSource(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $contentRepositoryPreset): ContentDimensionSourceInterface
    {
        assert(isset($contentRepositoryPreset['contentDimensionSource']['factoryObjectName']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have contentDimensionSource.factoryObjectName configured.', $contentRepositorySettings['preset']));
        $contentDimensionSourceFactory = $this->objectManager->get($contentRepositoryPreset['contentDimensionSource']['factoryObjectName']);
        if (!$contentDimensionSourceFactory instanceof ContentDimensionSourceFactoryInterface) {
            throw new \RuntimeException(sprintf('contentDimensionSource.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryId->value, NodeTypeManagerFactoryInterface::class, get_debug_type($contentDimensionSourceFactory)));
        }
        return $contentDimensionSourceFactory->build($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset['contentDimensionSource']);

    }

    private function buildPropertySerializer(array $contentRepositorySettings, array $contentRepositoryPreset): Serializer
    {
        assert(isset($contentRepositoryPreset['propertyConverters']) && is_array($contentRepositoryPreset['propertyConverters']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have propertyConverters configured, or the value is no array.', $contentRepositorySettings['preset']));
        $propertyConvertersConfiguration = (new PositionalArraySorter($contentRepositoryPreset['propertyConverters']))
            ->toArray();

        $normalizers = [];
        foreach ($propertyConvertersConfiguration as $propertyConverterConfiguration) {
            $normalizer = new $propertyConverterConfiguration['className'];
            if (!$normalizer instanceof NormalizerInterface && !$normalizer instanceof DenormalizerInterface) {
                throw new \InvalidArgumentException(
                    'Serializers can only be created of ' . NormalizerInterface::class
                    . ' and ' . DenormalizerInterface::class
                    . ', ' . get_class($normalizer) . ' given.',
                    1645386698
                );
            }
            $normalizers[] = $normalizer;
        }

        return new Serializer($normalizers);
    }

    private function buildProjectionsFactory(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $contentRepositoryPreset): ProjectionsFactory
    {
        assert(isset($contentRepositoryPreset['projections']) && is_array($contentRepositoryPreset['projections']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have projections configured, or the value is no array.', $contentRepositorySettings['preset']));
        $projectionsFactory = new ProjectionsFactory();
        foreach ((new PositionalArraySorter($contentRepositoryPreset['projections']))->toArray() as $projectionName => $projectionOptions) {
            $projectionFactory = $this->objectManager->get($projectionOptions['factoryObjectName']);
            if (!$projectionFactory instanceof ProjectionFactoryInterface) {
                throw new \RuntimeException(sprintf('Projection factory object name for projection "%s" (content repository "%s") is not an instance of %s but %s.', $projectionName, $contentRepositoryId->value, ProjectionFactoryInterface::class, get_debug_type($projectionFactory)));
            }
            $projectionsFactory->registerFactory(
                $projectionFactory,
                $projectionOptions['options'] ?? []
            );

            foreach (($projectionOptions['catchUpHooks'] ?? []) as $catchUpHookOptions) {
                $catchUpHookFactory = $this->objectManager->get($catchUpHookOptions['factoryObjectName']);
                if (!$catchUpHookFactory instanceof CatchUpHookFactoryInterface) {
                    throw new \RuntimeException(sprintf('CatchUpHook factory object name for projection "%s" (content repository "%s") is not an instance of %s but %s', $projectionName, $contentRepositoryId->value, CatchUpHookFactoryInterface::class, get_debug_type($catchUpHookFactory)));
                }
                $projectionsFactory->registerCatchUpHookFactory($projectionFactory, $catchUpHookFactory);
            }
        }
        return $projectionsFactory;
    }

    private function buildProjectionCatchUpTrigger(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $contentRepositoryPreset): ProjectionCatchUpTriggerInterface
    {
        assert(isset($contentRepositoryPreset['projectionCatchUpTrigger']['factoryObjectName']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have projectionCatchUpTrigger.factoryObjectName configured.', $contentRepositorySettings['preset']));
        $projectionCatchUpTriggerFactory = $this->objectManager->get($contentRepositoryPreset['projectionCatchUpTrigger']['factoryObjectName']);
        if (!$projectionCatchUpTriggerFactory instanceof ProjectionCatchUpTriggerFactoryInterface) {
            throw new \RuntimeException(sprintf('projectionCatchUpTrigger.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryId->value, ProjectionCatchUpTriggerFactoryInterface::class, get_debug_type($projectionCatchUpTriggerFactory)));
        }
        return $projectionCatchUpTriggerFactory->build($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset['projectionCatchUpTrigger'] ?? []);
    }

    private function buildUserIdProvider(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $contentRepositoryPreset): UserIdProviderInterface
    {
        assert(isset($contentRepositoryPreset['userIdProvider']['factoryObjectName']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have userIdProvider.factoryObjectName configured.', $contentRepositorySettings['preset']));
        $userIdProviderFactory = $this->objectManager->get($contentRepositoryPreset['userIdProvider']['factoryObjectName']);
        if (!$userIdProviderFactory instanceof UserIdProviderFactoryInterface) {
            throw new \RuntimeException(sprintf('userIdProvider.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryId->value, UserIdProviderFactoryInterface::class, get_debug_type($userIdProviderFactory)));
        }
        return $userIdProviderFactory->build($contentRepositoryId, $contentRepositorySettings, $contentRepositoryPreset);
    }

    private function buildClock(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $contentRepositoryPreset): ClockInterface
    {
        assert(isset($contentRepositoryPreset['clock']['factoryObjectName']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have clock.factoryObjectName configured.', $contentRepositorySettings['preset']));
        $clockFactory = $this->objectManager->get($contentRepositoryPreset['clock']['factoryObjectName']);
        if (!$clockFactory instanceof ClockFactoryInterface) {
            throw new \RuntimeException(sprintf('clock.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryIdentifier->value, ClockFactoryInterface::class, get_debug_type($clockFactory)));
        }
        return $clockFactory->build($contentRepositoryIdentifier, $contentRepositorySettings, $contentRepositoryPreset);
    }
}
