<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryFactory;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Factory\ProjectionsAndCatchUpHooksFactory;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\ContentRepositoryRegistry\Exception\ContentRepositoryNotFoundException;
use Neos\ContentRepositoryRegistry\Exception\InvalidConfigurationException;
use Neos\ContentRepositoryRegistry\Factory\Clock\ClockFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource\ContentDimensionSourceFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\EventStore\EventStoreFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\NodeTypeManager\NodeTypeManagerFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\ProjectionCatchUpTriggerFactoryInterface;
use Neos\ContentRepositoryRegistry\Factory\UserIdProvider\UserIdProviderFactoryInterface;
use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\Arrays;
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
     * @var array<string, ContentRepositoryFactory>
     */
    private array $factoryInstances = [];

    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        private readonly array $settings,
        private readonly ObjectManagerInterface $objectManager,
    ) {
    }

    /**
     * This is the main entry point for Neos / Flow installations to fetch a content repository.
     * A content repository is not a singleton and must be fetched by its identifier.
     *
     * To get a hold of a content repository identifier, it has to be passed along.
     *
     * For Neos web requests, the current content repository can be inferred by the domain and the connected site:
     * {@see \Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult::fromRequest()}
     * Or it has to be encoded manually as part of a query parameter.
     *
     * For CLI applications, it's a necessity to specify the content repository as argument from the outside,
     * generally via `--content-repository default`
     *
     * The content repository identifier should never be hard-coded without being aware of its implications.
     *
     * Hint: in case you are already in a service that is scoped to a content repository or a projection catchup hook,
     * the content repository will likely be already available via e.g. the service factory.
     *
     * @throws ContentRepositoryNotFoundException | InvalidConfigurationException
     */
    public function get(ContentRepositoryId $contentRepositoryId): ContentRepository
    {
        return $this->getFactory($contentRepositoryId)->getOrBuild();
    }

    /**
     * @return iterable<ContentRepositoryId>
     */
    public function getContentRepositoryIds(): iterable
    {
        /** @phpstan-ignore argument.type */
        return array_map(ContentRepositoryId::fromString(...), array_keys($this->settings['contentRepositories'] ?? []));
    }

    /**
     * @internal for test cases only
     */
    public function resetFactoryInstance(ContentRepositoryId $contentRepositoryId): void
    {
        if (array_key_exists($contentRepositoryId->value, $this->factoryInstances)) {
            unset($this->factoryInstances[$contentRepositoryId->value]);
        }
    }

    public function subgraphForNode(Node $node): ContentSubgraphInterface
    {
        $contentRepository = $this->get($node->contentRepositoryId);

        return $contentRepository->getContentGraph($node->workspaceName)->getSubgraph(
            $node->dimensionSpacePoint,
            $node->visibilityConstraints
        );
    }

    /**
     * @param ContentRepositoryId $contentRepositoryId
     * @param ContentRepositoryServiceFactoryInterface<T> $contentRepositoryServiceFactory
     * @return T
     * @throws ContentRepositoryNotFoundException | InvalidConfigurationException
     * @template T of ContentRepositoryServiceInterface
     */
    public function buildService(ContentRepositoryId $contentRepositoryId, ContentRepositoryServiceFactoryInterface $contentRepositoryServiceFactory): ContentRepositoryServiceInterface
    {
        return $this->getFactory($contentRepositoryId)->buildService($contentRepositoryServiceFactory);
    }

    /**
     * @throws ContentRepositoryNotFoundException | InvalidConfigurationException
     */
    private function getFactory(
        ContentRepositoryId $contentRepositoryId
    ): ContentRepositoryFactory {
        // This cache is CRUCIAL, because it ensures that the same CR always deals with the same objects internally, even if multiple services
        // are called on the same CR.
        if (!array_key_exists($contentRepositoryId->value, $this->factoryInstances)) {
            $this->factoryInstances[$contentRepositoryId->value] = $this->buildFactory($contentRepositoryId);
        }
        return $this->factoryInstances[$contentRepositoryId->value];
    }

    /**
     * @throws ContentRepositoryNotFoundException | InvalidConfigurationException
     */
    private function buildFactory(ContentRepositoryId $contentRepositoryId): ContentRepositoryFactory {
        if (!is_array($this->settings['contentRepositories'] ?? null)) {
            throw InvalidConfigurationException::fromMessage('No Content Repositories are configured');
        }

        if (!isset($this->settings['contentRepositories'][$contentRepositoryId->value]) || !is_array($this->settings['contentRepositories'][$contentRepositoryId->value])) {
            throw ContentRepositoryNotFoundException::notConfigured($contentRepositoryId);
        }
        $contentRepositorySettings = $this->settings['contentRepositories'][$contentRepositoryId->value];
        if (isset($contentRepositorySettings['preset'])) {
            is_string($contentRepositorySettings['preset']) || throw InvalidConfigurationException::fromMessage('Invalid "preset" configuration for Content Repository "%s". Expected string, got: %s', $contentRepositoryId->value, get_debug_type($contentRepositorySettings['preset']));
            if (!isset($this->settings['presets'][$contentRepositorySettings['preset']]) || !is_array($this->settings['presets'][$contentRepositorySettings['preset']])) {
                throw InvalidConfigurationException::fromMessage('Content Repository settings "%s" refer to a preset "%s", but there are not presets configured', $contentRepositoryId->value, $contentRepositorySettings['preset']);
            }
            $contentRepositorySettings = Arrays::arrayMergeRecursiveOverrule($this->settings['presets'][$contentRepositorySettings['preset']], $contentRepositorySettings);
            unset($contentRepositorySettings['preset']);
        }
        try {
            $clock = $this->buildClock($contentRepositoryId, $contentRepositorySettings);
            return new ContentRepositoryFactory(
                $contentRepositoryId,
                $this->buildEventStore($contentRepositoryId, $contentRepositorySettings, $clock),
                $this->buildNodeTypeManager($contentRepositoryId, $contentRepositorySettings),
                $this->buildContentDimensionSource($contentRepositoryId, $contentRepositorySettings),
                $this->buildPropertySerializer($contentRepositoryId, $contentRepositorySettings),
                $this->buildProjectionsFactory($contentRepositoryId, $contentRepositorySettings),
                $this->buildProjectionCatchUpTrigger($contentRepositoryId, $contentRepositorySettings),
                $this->buildUserIdProvider($contentRepositoryId, $contentRepositorySettings),
                $clock
            );
        } catch (\Exception $exception) {
            throw InvalidConfigurationException::fromException($contentRepositoryId, $exception);
        }
    }

    /** @param array<string, mixed> $contentRepositorySettings */
    private function buildEventStore(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, ClockInterface $clock): EventStoreInterface
    {
        isset($contentRepositorySettings['eventStore']['factoryObjectName']) || throw InvalidConfigurationException::fromMessage('Content repository "%s" does not have eventStore.factoryObjectName configured.', $contentRepositoryId->value);
        $eventStoreFactory = $this->objectManager->get($contentRepositorySettings['eventStore']['factoryObjectName']);
        if (!$eventStoreFactory instanceof EventStoreFactoryInterface) {
            throw InvalidConfigurationException::fromMessage('eventStore.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryId->value, EventStoreFactoryInterface::class, get_debug_type($eventStoreFactory));
        }
        return $eventStoreFactory->build($contentRepositoryId, $contentRepositorySettings['eventStore']['options'] ?? [], $clock);
    }

    /** @param array<string, mixed> $contentRepositorySettings */
    private function buildNodeTypeManager(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings): NodeTypeManager
    {
        isset($contentRepositorySettings['nodeTypeManager']['factoryObjectName']) || throw InvalidConfigurationException::fromMessage('Content repository "%s" does not have nodeTypeManager.factoryObjectName configured.', $contentRepositoryId->value);
        $nodeTypeManagerFactory = $this->objectManager->get($contentRepositorySettings['nodeTypeManager']['factoryObjectName']);
        if (!$nodeTypeManagerFactory instanceof NodeTypeManagerFactoryInterface) {
            throw InvalidConfigurationException::fromMessage('nodeTypeManager.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryId->value, NodeTypeManagerFactoryInterface::class, get_debug_type($nodeTypeManagerFactory));
        }
        return $nodeTypeManagerFactory->build($contentRepositoryId, $contentRepositorySettings['nodeTypeManager']['options'] ?? []);
    }

    /** @param array<string, mixed> $contentRepositorySettings */
    private function buildContentDimensionSource(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings): ContentDimensionSourceInterface
    {
        isset($contentRepositorySettings['contentDimensionSource']['factoryObjectName']) || throw InvalidConfigurationException::fromMessage('Content repository "%s" does not have contentDimensionSource.factoryObjectName configured.', $contentRepositoryId->value);
        $contentDimensionSourceFactory = $this->objectManager->get($contentRepositorySettings['contentDimensionSource']['factoryObjectName']);
        if (!$contentDimensionSourceFactory instanceof ContentDimensionSourceFactoryInterface) {
            throw InvalidConfigurationException::fromMessage('contentDimensionSource.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryId->value, NodeTypeManagerFactoryInterface::class, get_debug_type($contentDimensionSourceFactory));
        }
        // Note: contentDimensions can be specified on the top-level for easier use.
        // They can still be overridden in the specific "contentDimensionSource" options
        $options = $contentRepositorySettings['contentDimensionSource']['options'] ?? [];
        if (isset($contentRepositorySettings['contentDimensions'])) {
            $options['contentDimensions'] = Arrays::arrayMergeRecursiveOverrule($contentRepositorySettings['contentDimensions'], $options['contentDimensions'] ?? []);
        }
        return $contentDimensionSourceFactory->build($contentRepositoryId, $options);

    }

    /** @param array<string, mixed> $contentRepositorySettings */
    private function buildPropertySerializer(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings): Serializer
    {
        (isset($contentRepositorySettings['propertyConverters']) && is_array($contentRepositorySettings['propertyConverters'])) || throw InvalidConfigurationException::fromMessage('Content repository "%s" does not have propertyConverters configured, or the value is no array.', $contentRepositoryId->value);
        $propertyConvertersConfiguration = (new PositionalArraySorter($contentRepositorySettings['propertyConverters']))->toArray();

        $normalizers = [];
        foreach ($propertyConvertersConfiguration as $propertyConverterConfiguration) {
            $normalizer = new $propertyConverterConfiguration['className']();
            if (!$normalizer instanceof NormalizerInterface && !$normalizer instanceof DenormalizerInterface) {
                throw InvalidConfigurationException::fromMessage('Serializers can only be created of %s and %s, %s given', NormalizerInterface::class, DenormalizerInterface::class, get_debug_type($normalizer));
            }
            $normalizers[] = $normalizer;
        }
        return new Serializer($normalizers);
    }

    /** @param array<string, mixed> $contentRepositorySettings */
    private function buildProjectionsFactory(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings): ProjectionsAndCatchUpHooksFactory
    {
        (isset($contentRepositorySettings['projections']) && is_array($contentRepositorySettings['projections'])) || throw InvalidConfigurationException::fromMessage('Content repository "%s" does not have projections configured, or the value is no array.', $contentRepositoryId->value);
        $projectionsFactory = new ProjectionsAndCatchUpHooksFactory();
        foreach ($contentRepositorySettings['projections'] as $projectionName => $projectionOptions) {
            if ($projectionOptions === null) {
                continue;
            }
            $projectionFactory = $this->objectManager->get($projectionOptions['factoryObjectName']);
            if (!$projectionFactory instanceof ProjectionFactoryInterface) {
                throw InvalidConfigurationException::fromMessage('Projection factory object name for projection "%s" (content repository "%s") is not an instance of %s but %s.', $projectionName, $contentRepositoryId->value, ProjectionFactoryInterface::class, get_debug_type($projectionFactory));
            }
            $projectionsFactory->registerFactory($projectionFactory, $projectionOptions['options'] ?? []);
            foreach (($projectionOptions['catchUpHooks'] ?? []) as $catchUpHookOptions) {
                if ($catchUpHookOptions === null) {
                    continue;
                }
                $catchUpHookFactory = $this->objectManager->get($catchUpHookOptions['factoryObjectName']);
                if (!$catchUpHookFactory instanceof CatchUpHookFactoryInterface) {
                    throw InvalidConfigurationException::fromMessage('CatchUpHook factory object name for projection "%s" (content repository "%s") is not an instance of %s but %s', $projectionName, $contentRepositoryId->value, CatchUpHookFactoryInterface::class, get_debug_type($catchUpHookFactory));
                }
                $projectionsFactory->registerCatchUpHookFactory($projectionFactory, $catchUpHookFactory);
            }
        }
        return $projectionsFactory;
    }

    /** @param array<string, mixed> $contentRepositorySettings */
    private function buildProjectionCatchUpTrigger(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings): ProjectionCatchUpTriggerInterface
    {
        isset($contentRepositorySettings['projectionCatchUpTrigger']['factoryObjectName']) || throw InvalidConfigurationException::fromMessage('Content repository "%s" does not have projectionCatchUpTrigger.factoryObjectName configured.', $contentRepositoryId->value);
        $projectionCatchUpTriggerFactory = $this->objectManager->get($contentRepositorySettings['projectionCatchUpTrigger']['factoryObjectName']);
        if (!$projectionCatchUpTriggerFactory instanceof ProjectionCatchUpTriggerFactoryInterface) {
            throw InvalidConfigurationException::fromMessage('projectionCatchUpTrigger.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryId->value, ProjectionCatchUpTriggerFactoryInterface::class, get_debug_type($projectionCatchUpTriggerFactory));
        }
        return $projectionCatchUpTriggerFactory->build($contentRepositoryId, $contentRepositorySettings['projectionCatchUpTrigger']['options'] ?? []);
    }

    /** @param array<string, mixed> $contentRepositorySettings */
    private function buildUserIdProvider(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings): UserIdProviderInterface
    {
        isset($contentRepositorySettings['userIdProvider']['factoryObjectName']) || throw InvalidConfigurationException::fromMessage('Content repository "%s" does not have userIdProvider.factoryObjectName configured.', $contentRepositoryId->value);
        $userIdProviderFactory = $this->objectManager->get($contentRepositorySettings['userIdProvider']['factoryObjectName']);
        if (!$userIdProviderFactory instanceof UserIdProviderFactoryInterface) {
            throw InvalidConfigurationException::fromMessage('userIdProvider.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryId->value, UserIdProviderFactoryInterface::class, get_debug_type($userIdProviderFactory));
        }
        return $userIdProviderFactory->build($contentRepositoryId, $contentRepositorySettings['userIdProvider']['options'] ?? []);
    }

    /** @param array<string, mixed> $contentRepositorySettings */
    private function buildClock(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings): ClockInterface
    {
        isset($contentRepositorySettings['clock']['factoryObjectName']) || throw InvalidConfigurationException::fromMessage('Content repository "%s" does not have clock.factoryObjectName configured.', $contentRepositoryIdentifier->value);
        $clockFactory = $this->objectManager->get($contentRepositorySettings['clock']['factoryObjectName']);
        if (!$clockFactory instanceof ClockFactoryInterface) {
            throw InvalidConfigurationException::fromMessage('clock.factoryObjectName for content repository "%s" is not an instance of %s but %s.', $contentRepositoryIdentifier->value, ClockFactoryInterface::class, get_debug_type($clockFactory));
        }
        return $clockFactory->build($contentRepositoryIdentifier, $contentRepositorySettings['clock']['options'] ?? []);
    }
}
