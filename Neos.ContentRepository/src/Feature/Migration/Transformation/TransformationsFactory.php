<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Transformation;

use Neos\ContentRepository\Feature\Migration\MigrationException;
use Neos\ContentRepository\Feature\Migration\MigrationCommandHandler;
use Psr\Container\ContainerInterface;

/**
 * Implementation detail of {@see MigrationCommandHandler}
 */
class TransformationsFactory
{
    public function __construct(
        private readonly ContainerInterface $container
    )
    {
    }

    /**
     * @param array<int|string,array<string,mixed>> $transformationConfigurations
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
     * @param array<string,mixed> $transformationConfiguration
     */
    protected function buildTransformationObject(
        array $transformationConfiguration
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface
    {
        $transformationFactoryClassName = $this->resolveTransformationClassName($transformationConfiguration['type']);
        $transformationFactory = $this->container->get($transformationFactoryClassName);
        assert($transformationFactory instanceof TransformationFactoryInterface);
        return $transformationFactory->build($transformationConfiguration['settings'] ?? []);
    }

    /**
     * Tries to resolve the given transformation name into a class name.
     *
     * The name can be a fully qualified class name or a name relative to the
     * Neos\ContentRepository\Feature\Migration\Transformation namespace.
     *
     * @param string $transformationName
     * @return string
     * @throws MigrationException
     */
    protected function resolveTransformationClassName(string $transformationName): string
    {
        if (class_exists($transformationName)) {
            return $transformationName;
        }

        if ($transformationName === 'AddDimensions') {
            throw new MigrationException(
                'The "AddDimensions" transformation from the legacy content repository has been replaced'
                . ' by the "AddDimensionSpecialization" transformation in the event-sourced content repository.'
                . ' Please adjust your node migrations.',
                1637178179
            );
        }

        if ($transformationName === 'RenameDimension') {
            throw new MigrationException(
                'The "RenameDimension" transformation from the legacy content repository has been replaced'
                . ' by the "MoveToDimensionSpacePoints" transformation in the event-sourced content repository.'
                . ' Please adjust your node migrations.',
                1637178184
            );
        }

        if ($transformationName === 'RenameNode') {
            throw new MigrationException(
                'The "RenameNode" transformation from the legacy content repository has been replaced'
                . ' by the "RenameNodeAggregate" transformation in the event-sourced content repository.'
                . ' Please adjust your node migrations.',
                1637178234
            );
        }

        if ($transformationName === 'SetDimensions') {
            throw new MigrationException(
                'The "SetDimensions" transformation from the legacy content repository has been replaced'
                . ' by the "AddDimensionSpecialization" and "MoveToDimensionSpacePoints" transformation'
                . ' in the event-sourced content repository. Please adjust your node migrations.',
                1637178280
            );
        }

        return 'Neos\ContentRepository\Feature\Migration\Transformation\\' . $transformationName . 'TransformationFactory';
    }
}
