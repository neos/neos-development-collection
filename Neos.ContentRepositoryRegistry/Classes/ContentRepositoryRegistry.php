<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Factory\ContentRepositoryFactory;
use Neos\ContentRepository\Factory\ProjectionsFactory;
use Neos\ContentRepository\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepositoryRegistry\Exception\ContentRepositoryNotFound;
use Neos\ContentRepositoryRegistry\Exception\InvalidConfigurationException;
use Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource\ContentDimensionSourceFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\EventStore\EventStoreFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\NodeTypeManager\NodeTypeManagerFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\ProjectionCatchUpTriggerFactoryInterface;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
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
    private static array $instances = [];

    /**
     * @param array<mixed> $settings
     */
    public function __construct(
        private readonly array $settings,
        private readonly ObjectManagerInterface $objectManager
    ) {}

    /**
     * @throws ContentRepositoryNotFound | InvalidConfigurationException
     */
    public function get(ContentRepositoryIdentifier $contentRepositoryId): ContentRepository
    {
        if (!array_key_exists($contentRepositoryId->value, self::$instances)) {
            self::$instances[$contentRepositoryId->value] = $this->buildInstance($contentRepositoryId);
        }
        return self::$instances[$contentRepositoryId->value];
    }

    /**
     * @throws ContentRepositoryNotFound | InvalidConfigurationException
     */
    private function buildInstance(ContentRepositoryIdentifier $contentRepositoryIdentifier): ContentRepository
    {
        assert(is_array($this->settings['contentRepositories']));
        assert(isset($this->settings['contentRepositories'][$contentRepositoryIdentifier->value]) && is_array($this->settings['contentRepositories'][$contentRepositoryIdentifier->value]), ContentRepositoryNotFound::notConfigured($contentRepositoryIdentifier));
        $contentRepositorySettings = $this->settings['contentRepositories'][$contentRepositoryIdentifier->value];
        if (isset($contentRepositorySettings['preset'])) {
            assert(isset($this->settings['presets']) && is_array($this->settings['presets']), InvalidConfigurationException::fromMessage('Content repository settings "%s" refer to a preset "%s", but there are not presets configured', $contentRepositoryIdentifier->value, $contentRepositorySettings['preset']));
            assert(isset($this->settings['presets'][$contentRepositorySettings['preset']]) && is_array($this->settings['presets'][$contentRepositorySettings['preset']]), InvalidConfigurationException::missingPreset($contentRepositoryIdentifier, $contentRepositorySettings['preset']));
            $contentRepositorySettings = Arrays::arrayMergeRecursiveOverrule($this->settings['presets'][$contentRepositorySettings['preset']], $contentRepositorySettings);
        }
        try {
            $contentRepositoryFactory = new ContentRepositoryFactory(
                $contentRepositoryIdentifier,
                $this->buildEventStore($contentRepositoryIdentifier, $contentRepositorySettings),
                $this->buildNodeTypeManager($contentRepositoryIdentifier, $contentRepositorySettings),
                $this->buildContentDimensionSource($contentRepositoryIdentifier, $contentRepositorySettings),
                $this->buildPropertySerializer($contentRepositorySettings),
                $this->buildProjectionsFactory($contentRepositorySettings),
                $this->buildProjectionCatchUpTrigger($contentRepositoryIdentifier, $contentRepositorySettings),
            );
            return $contentRepositoryFactory->build();
        } catch (\Exception $exception) {
            throw InvalidConfigurationException::fromException($contentRepositoryIdentifier, $exception);
        }
    }

    private function buildEventStore(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings): EventStoreInterface
    {
        $eventStoreFactory = $this->objectManager->get($contentRepositorySettings['eventStore']['factoryObjectName']);
        if (!$eventStoreFactory instanceof EventStoreFactoryInterface) {
            throw new \RuntimeException(sprintf('eventStore.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryIdentifier->value, EventStoreFactoryInterface::class, get_debug_type($eventStoreFactory)));
        }
        return $eventStoreFactory->build($contentRepositoryIdentifier, $contentRepositorySettings['eventStore']['options'] ?? []);
    }

    private function buildNodeTypeManager(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings): NodeTypeManager
    {
        $nodeTypeManagerFactory = $this->objectManager->get($contentRepositorySettings['nodeTypeManager']['factoryObjectName']);
        if (!$nodeTypeManagerFactory instanceof NodeTypeManagerFactoryInterface) {
            throw new \RuntimeException(sprintf('nodeTypeManager.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryIdentifier->value, NodeTypeManagerFactoryInterface::class, get_debug_type($nodeTypeManagerFactory)));
        }
        return $nodeTypeManagerFactory->build($contentRepositoryIdentifier, $contentRepositorySettings['nodeTypeManager']['options'] ?? []);
    }

    private function buildContentDimensionSource(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings): ContentDimensionSourceInterface
    {
        $contentDimensionSourceFactory = $this->objectManager->get($contentRepositorySettings['contentDimensionSource']['factoryObjectName']);
        if (!$contentDimensionSourceFactory instanceof ContentDimensionSourceFactoryInterface) {
            throw new \RuntimeException(sprintf('contentDimensionSource.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryIdentifier->value, NodeTypeManagerFactoryInterface::class, get_debug_type($contentDimensionSourceFactory)));
        }
        return $contentDimensionSourceFactory->build($contentRepositoryIdentifier, $contentRepositorySettings['contentDimensionSource']['options'] ?? []);

    }

    private function buildPropertySerializer(array $contentRepositorySettings): Serializer
    {
        $propertyConvertersConfiguration = (new PositionalArraySorter($contentRepositorySettings['propertyConverters']))
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

    private function buildProjectionsFactory(array $contentRepositorySettings): ProjectionsFactory
    {
        $projectionsFactory = new ProjectionsFactory();
        foreach ((new PositionalArraySorter($contentRepositorySettings['projections']))->toArray() as $projectionName => $projectionOptions) {
            $projectionFactory = $this->objectManager->get($projectionOptions['factoryObjectName']);
            if (!$projectionFactory instanceof ProjectionFactoryInterface) {
                throw new \RuntimeException(sprintf('Projection factory object name for projection "%s" (content repository "%s") is not an instance of %s but %s in content repository "%s"', $projectionName, $contentRepositoryId->value, ProjectionFactoryInterface::class, get_debug_type($projectionFactory)));
            }
            $projectionsFactory->registerFactory(
                $projectionFactory,
                $projectionOptions['options'] ?? []
            );
        }
        return $projectionsFactory;
    }

    private function buildProjectionCatchUpTrigger(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings): ProjectionCatchUpTriggerInterface
    {
        $projectionCatchUpTriggerFactory = $this->objectManager->get($contentRepositorySettings['projectionCatchUpTrigger']['factoryObjectName']);
        if (!$projectionCatchUpTriggerFactory instanceof ProjectionCatchUpTriggerFactoryInterface) {
            throw new \RuntimeException(sprintf('projectionCatchUpTrigger.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryIdentifier->value, ProjectionCatchUpTriggerFactoryInterface::class, get_debug_type($projectionCatchUpTriggerFactory)));
        }
        return $projectionCatchUpTriggerFactory->build($contentRepositoryIdentifier, $contentRepositorySettings['projectionCatchUpTrigger']['options'] ?? []);
    }
}
