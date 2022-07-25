<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Factory\ProjectionsFactory;
use Neos\ContentRepository\Projection\ProjectionFactoryInterface;
use Neos\ContentRepositoryRegistry\Exception\ContentRepositoryNotFound;
use Neos\ContentRepositoryRegistry\Exception\InvalidConfigurationException;
use Neos\ContentRepositoryRegistry\Factories\ContentRepositoryFactory;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\Arrays;
use Neos\Utility\PositionalArraySorter;

#[Flow\Scope("singleton")]
final class ContentRepositoryRegistry
{
    /**
     * @var array<string, ContentRepository>
     */
    private static array $instances = [];

    /**
     * @param array<mixed> $settings
     * @param ContentRepositoryFactory $factory
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
    private function buildInstance(ContentRepositoryIdentifier $contentRepositoryId): ContentRepository
    {
        assert(is_array($this->settings['contentRepositories']));
        assert(isset($this->settings['contentRepositories'][$contentRepositoryId->value]) && is_array($this->settings['contentRepositories'][$contentRepositoryId->value]), ContentRepositoryNotFound::notConfigured($contentRepositoryId));
        $contentRepositorySettings = $this->settings['contentRepositories'][$contentRepositoryId->value];
        if (isset($contentRepositorySettings['preset'])) {
            assert(isset($this->settings['presets']) && is_array($this->settings['presets']), InvalidConfigurationException::fromMessage('Content repository settings "%s" refer to a preset "%s", but there are not presets configured', $contentRepositoryId->value, $contentRepositorySettings['preset']));
            assert(isset($this->settings['presets'][$contentRepositorySettings['preset']]) && is_array($this->settings['presets'][$contentRepositorySettings['preset']]), InvalidConfigurationException::missingPreset($contentRepositoryId, $contentRepositorySettings['preset']));
            $contentRepositorySettings = Arrays::arrayMergeRecursiveOverrule($contentRepositorySettings, $this->settings['presets'][$contentRepositorySettings['preset']]);
        }
        array_walk_recursive($contentRepositorySettings, static fn(&$value) => $value = is_string($value) ? str_replace('{contentRepositoryId}', $contentRepositoryId->value, $value) : $value);
        try {
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

            return (new \Neos\ContentRepository\Factory\ContentRepositoryFactory(
                $projectionsFactory
            ))->build();
        } catch (\Exception $exception) {
            throw InvalidConfigurationException::fromException($contentRepositoryId, $exception);
        }
    }

}
