<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\Migration\Transformations;

use Neos\ContentRepository\Migration\Exception\MigrationException;
use Neos\EventSourcedContentRepository\Domain\Context\Migration\Dto\Transformations;
use Neos\EventSourcedContentRepository\Domain\Context\Migration\NodeMigrationService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\ObjectAccess;

/**
 * Implementation detail of {@see NodeMigrationService}
 * @Flow\Scope("singleton")
 */
class TransformationFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $transformationConfiguration
     * @throws MigrationException
     */
    public function buildTransformation(array $transformationConfigurations): Transformations
    {
        $transformationObjects = [];
        foreach ($transformationConfigurations as $transformationConfiguration) {
            $transformationObjects[] = $this->buildTransformationObject($transformationConfiguration);
        }

        return new Transformations($transformationObjects);
    }

    /**
     * Builds a transformation object from the given configuration.
     *
     * @param array $transformationConfiguration
     * @throws MigrationException if a given setting is not supported
     */
    protected function buildTransformationObject(array $transformationConfiguration)
    {
        $transformationClassName = $this->resolveTransformationClassName($transformationConfiguration['type']);
        $transformation = new $transformationClassName();

        if (isset($transformationConfiguration['settings']) && is_array($transformationConfiguration['settings'])) {
            foreach ($transformationConfiguration['settings'] as $settingName => $settingValue) {
                if (!ObjectAccess::setProperty($transformation, $settingName, $settingValue)) {
                    throw new MigrationException('Cannot set setting "' . $settingName . '" on transformation "' . $transformationClassName . '" , check your configuration.', 1343293094);
                }
            }
        }

        return $transformation;
    }

    /**
     * Tries to resolve the given transformation name into a class name.
     *
     * The name can be a fully qualified class name or a name relative to the
     * Neos\EventSourcedContentRepository\Domain\Context\NodeMigration\Transformations namespace.
     *
     * @param string $transformationName
     * @return string
     * @throws MigrationException
     */
    protected function resolveTransformationClassName(string $transformationName): string
    {
        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($transformationName);
        if ($resolvedObjectName !== null) {
            return $resolvedObjectName;
        }

        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('Neos\EventSourcedContentRepository\Domain\Context\Migration\Transformations\\' . $transformationName);
        if ($resolvedObjectName !== null) {
            return $resolvedObjectName;
        }

        throw new MigrationException('A transformation with the name "' . $transformationName . '" could not be found.', 1343293064);
    }
}
