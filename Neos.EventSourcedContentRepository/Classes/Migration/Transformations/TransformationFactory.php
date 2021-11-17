<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Migration\Transformations;

use Neos\ContentRepository\Migration\Exception\MigrationException;
use Neos\EventSourcedContentRepository\Migration\Dto\Transformations;
use Neos\EventSourcedContentRepository\Migration\MigrationCommandHandler;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\ObjectAccess;

/**
 * Implementation detail of {@see MigrationCommandHandler}
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

        if ($transformationName === 'AddDimensions') {
            throw new MigrationException('The "AddDimensions" transformation from the legacy content repository has been replaced by the "AddDimensionSpecialization" transformation in the event-sourced content repository. Please adjust your node migrations.', 1637178179);
        }

        if ($transformationName === 'RenameDimension') {
            throw new MigrationException('The "RenameDimension" transformation from the legacy content repository has been replaced by the "MoveToDimensionSpacePoints" transformation in the event-sourced content repository. Please adjust your node migrations.', 1637178184);
        }

        if ($transformationName === 'RenameNode') {
            throw new MigrationException('The "RenameNode" transformation from the legacy content repository has been replaced by the "RenameNodeAggregate" transformation in the event-sourced content repository. Please adjust your node migrations.', 1637178234);
        }

        if ($transformationName === 'SetDimensions') {
            throw new MigrationException('The "SetDimensions" transformation from the legacy content repository has been replaced by the "AddDimensionSpecialization" and "MoveToDimensionSpacePoints" transformation in the event-sourced content repository. Please adjust your node migrations.', 1637178280);
        }

        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('Neos\EventSourcedContentRepository\Migration\Transformations\\' . $transformationName);
        if ($resolvedObjectName !== null) {
            return $resolvedObjectName;
        }

        throw new MigrationException('A transformation with the name "' . $transformationName . '" could not be found.', 1343293064);
    }
}
