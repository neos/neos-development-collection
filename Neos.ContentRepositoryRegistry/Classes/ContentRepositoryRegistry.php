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
use Neos\ContentRepositoryRegistry\Exception\ContentRepositoryNotFound;
use Neos\ContentRepositoryRegistry\Exception\InvalidConfigurationException;
use Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource\ContentDimensionSourceFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\EventStore\EventStoreFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\NodeTypeManager\NodeTypeManagerFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\ProjectionCatchUpTriggerFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\Arrays;
use Neos\Utility\PositionalArraySorter;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

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
    public function get(ContentRepositoryId $contentRepositoryIdentifier): ContentRepository
    {
        if (!array_key_exists($contentRepositoryIdentifier->value, $this->contentRepositoryInstances)) {
            $this->contentRepositoryInstances[$contentRepositoryIdentifier->value] = $this->getFactory($contentRepositoryIdentifier)->build();
        }
        return $this->contentRepositoryInstances[$contentRepositoryIdentifier->value];
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
     * @param ContentRepositoryServiceFactoryInterface<T> $contentRepositoryServiceFactory
     * @return T
     * @throws ContentRepositoryNotFound | InvalidConfigurationException
     * @template T of ContentRepositoryServiceInterface
     */
    public function getService(ContentRepositoryId $contentRepositoryIdentifier, ContentRepositoryServiceFactoryInterface $contentRepositoryServiceFactory): ContentRepositoryServiceInterface
    {
        if (!isset($this->contentRepositoryServiceInstances[$contentRepositoryIdentifier->value][get_class($contentRepositoryServiceFactory)])) {
            $this->contentRepositoryServiceInstances[$contentRepositoryIdentifier->value][get_class($contentRepositoryServiceFactory)] = $this->getFactory($contentRepositoryIdentifier)->buildService($contentRepositoryServiceFactory);
        }
        return $this->contentRepositoryServiceInstances[$contentRepositoryIdentifier->value][get_class($contentRepositoryServiceFactory)];
    }


    /**
     * @throws ContentRepositoryNotFound | InvalidConfigurationException
     */
    private function getFactory(ContentRepositoryId $contentRepositoryIdentifier): ContentRepositoryFactory
    {
        // This cache is CRUCIAL, because it ensures that the same CR always deals with the same objects internally, even if multiple services
        // are called on the same CR.
        if (!array_key_exists($contentRepositoryIdentifier->value, $this->factoryInstances)) {
            $this->factoryInstances[$contentRepositoryIdentifier->value] = $this->buildFactory($contentRepositoryIdentifier);
        }
        return $this->factoryInstances[$contentRepositoryIdentifier->value];
    }

    /**
     * @throws ContentRepositoryNotFound | InvalidConfigurationException
     */
    private function buildFactory(ContentRepositoryId $contentRepositoryIdentifier): ContentRepositoryFactory
    {
        assert(is_array($this->settings['contentRepositories']));
        assert(isset($this->settings['contentRepositories'][$contentRepositoryIdentifier->value]) && is_array($this->settings['contentRepositories'][$contentRepositoryIdentifier->value]), ContentRepositoryNotFound::notConfigured($contentRepositoryIdentifier));
        $contentRepositorySettings = $this->settings['contentRepositories'][$contentRepositoryIdentifier->value];
        assert(is_string($contentRepositorySettings['preset']));

        assert(isset($this->settings['presets']) && is_array($this->settings['presets']), InvalidConfigurationException::fromMessage('Content repository settings "%s" refer to a preset "%s", but there are not presets configured', $contentRepositoryIdentifier->value, $contentRepositorySettings['preset']));
        assert(isset($this->settings['presets'][$contentRepositorySettings['preset']]) && is_array($this->settings['presets'][$contentRepositorySettings['preset']]), InvalidConfigurationException::missingPreset($contentRepositoryIdentifier, $contentRepositorySettings['preset']));
        $contentRepositoryPreset = $this->settings['presets'][$contentRepositorySettings['preset']];
        try {
            return new ContentRepositoryFactory(
                $contentRepositoryIdentifier,
                $this->buildEventStore($contentRepositoryIdentifier, $contentRepositorySettings, $contentRepositoryPreset),
                $this->buildNodeTypeManager($contentRepositoryIdentifier, $contentRepositorySettings, $contentRepositoryPreset),
                $this->buildContentDimensionSource($contentRepositoryIdentifier, $contentRepositorySettings, $contentRepositoryPreset),
                $this->buildPropertySerializer($contentRepositorySettings, $contentRepositoryPreset),
                $this->buildProjectionsFactory($contentRepositoryIdentifier, $contentRepositorySettings, $contentRepositoryPreset),
                $this->buildProjectionCatchUpTrigger($contentRepositoryIdentifier, $contentRepositorySettings, $contentRepositoryPreset),
            );
        } catch (\Exception $exception) {
            throw InvalidConfigurationException::fromException($contentRepositoryIdentifier, $exception);
        }
    }

    private function buildEventStore(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $contentRepositoryPreset): EventStoreInterface
    {
        assert(isset($contentRepositoryPreset['eventStore']['factoryObjectName']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have eventStore.factoryObjectName configured.', $contentRepositorySettings['preset']));
        $eventStoreFactory = $this->objectManager->get($contentRepositoryPreset['eventStore']['factoryObjectName']);
        if (!$eventStoreFactory instanceof EventStoreFactoryInterface) {
            throw new \RuntimeException(sprintf('eventStore.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryIdentifier->value, EventStoreFactoryInterface::class, get_debug_type($eventStoreFactory)));
        }
        return $eventStoreFactory->build($contentRepositoryIdentifier, $contentRepositorySettings, $contentRepositoryPreset['eventStore']);
    }

    private function buildNodeTypeManager(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $contentRepositoryPreset): NodeTypeManager
    {
        assert(isset($contentRepositoryPreset['nodeTypeManager']['factoryObjectName']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have nodeTypeManager.factoryObjectName configured.', $contentRepositorySettings['preset']));
        $nodeTypeManagerFactory = $this->objectManager->get($contentRepositoryPreset['nodeTypeManager']['factoryObjectName']);
        if (!$nodeTypeManagerFactory instanceof NodeTypeManagerFactoryInterface) {
            throw new \RuntimeException(sprintf('nodeTypeManager.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryIdentifier->value, NodeTypeManagerFactoryInterface::class, get_debug_type($nodeTypeManagerFactory)));
        }
        return $nodeTypeManagerFactory->build($contentRepositoryIdentifier, $contentRepositorySettings, $contentRepositoryPreset['nodeTypeManager']);
    }

    private function buildContentDimensionSource(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $contentRepositoryPreset): ContentDimensionSourceInterface
    {
        assert(isset($contentRepositoryPreset['contentDimensionSource']['factoryObjectName']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have contentDimensionSource.factoryObjectName configured.', $contentRepositorySettings['preset']));
        $contentDimensionSourceFactory = $this->objectManager->get($contentRepositoryPreset['contentDimensionSource']['factoryObjectName']);
        if (!$contentDimensionSourceFactory instanceof ContentDimensionSourceFactoryInterface) {
            throw new \RuntimeException(sprintf('contentDimensionSource.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryIdentifier->value, NodeTypeManagerFactoryInterface::class, get_debug_type($contentDimensionSourceFactory)));
        }
        return $contentDimensionSourceFactory->build($contentRepositoryIdentifier, $contentRepositorySettings, $contentRepositoryPreset['contentDimensionSource']);

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

    private function buildProjectionsFactory(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $contentRepositoryPreset): ProjectionsFactory
    {
        assert(isset($contentRepositoryPreset['projections']) && is_array($contentRepositoryPreset['projections']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have projections configured, or the value is no array.', $contentRepositorySettings['preset']));
        $projectionsFactory = new ProjectionsFactory();
        foreach ((new PositionalArraySorter($contentRepositoryPreset['projections']))->toArray() as $projectionName => $projectionOptions) {
            $projectionFactory = $this->objectManager->get($projectionOptions['factoryObjectName']);
            if (!$projectionFactory instanceof ProjectionFactoryInterface) {
                throw new \RuntimeException(sprintf('Projection factory object name for projection "%s" (content repository "%s") is not an instance of %s but %s.', $projectionName, $contentRepositoryIdentifier->value, ProjectionFactoryInterface::class, get_debug_type($projectionFactory)));
            }
            $projectionsFactory->registerFactory(
                $projectionFactory,
                $projectionOptions['options'] ?? []
            );

            foreach (($projectionOptions['catchUpHooks'] ?? []) as $catchUpHookOptions) {
                $catchUpHookFactory = $this->objectManager->get($catchUpHookOptions['factoryObjectName']);
                if (!$catchUpHookFactory instanceof CatchUpHookFactoryInterface) {
                    throw new \RuntimeException(sprintf('CatchUpHook factory object name for projection "%s" (content repository "%s") is not an instance of %s but %s', $projectionName, $contentRepositoryIdentifier->value, CatchUpHookFactoryInterface::class, get_debug_type($catchUpHookFactory)));
                }
                $projectionsFactory->registerCatchUpHookFactory($projectionFactory, $catchUpHookFactory);
            }
        }
        return $projectionsFactory;
    }

    private function buildProjectionCatchUpTrigger(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $contentRepositoryPreset): ProjectionCatchUpTriggerInterface
    {
        assert(isset($contentRepositoryPreset['projectionCatchUpTrigger']['factoryObjectName']), InvalidConfigurationException::fromMessage('Content repository preset "%s" does not have projectionCatchUpTrigger.factoryObjectName configured.', $contentRepositorySettings['preset']));
        $projectionCatchUpTriggerFactory = $this->objectManager->get($contentRepositoryPreset['projectionCatchUpTrigger']['factoryObjectName']);
        if (!$projectionCatchUpTriggerFactory instanceof ProjectionCatchUpTriggerFactoryInterface) {
            throw new \RuntimeException(sprintf('projectionCatchUpTrigger.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryIdentifier->value, ProjectionCatchUpTriggerFactoryInterface::class, get_debug_type($projectionCatchUpTriggerFactory)));
        }
        return $projectionCatchUpTriggerFactory->build($contentRepositoryIdentifier, $contentRepositorySettings, $contentRepositoryPreset['projectionCatchUpTrigger'] ?? []);
    }
}
