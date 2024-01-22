<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Transformation;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\NodeMigration\MigrationException;
use Neos\ContentRepository\NodeMigration\NodeMigrationService;

/**
 * Implementation detail of {@see NodeMigrationService}
 */
class TransformationsFactory
{
    /**
     * @var array<string,TransformationFactoryInterface>
     */
    private array $transformationFactories = [];

    public function __construct(
        private readonly ContentRepository $contentRepository
    ) {
    }

    public function registerTransformation(string $transformationIdentifier, TransformationFactoryInterface $transformationFactory): self
    {
        $this->transformationFactories[$transformationIdentifier] = $transformationFactory;
        return $this;
    }

    /**
     * @param array<int|string,array<string,mixed>> $transformationConfigurations
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws MigrationException
     */
    protected function buildTransformationObject(
        array $transformationConfiguration
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface
    {
        $transformationFactory = $this->resolveTransformationFactory($transformationConfiguration['type']);
        return $transformationFactory->build($transformationConfiguration['settings'] ?? [], $this->contentRepository);
    }

    /**
     * Tries to resolve the given transformation name into a class name.
     *
     * The name can be a fully qualified class name or a name relative to the
     * Neos\ContentRepository\NodeMigration\Transformation namespace.
     *
     * @param string $transformationName
     * @return string
     * @throws MigrationException
     */
    protected function resolveTransformationFactory(string $transformationName): TransformationFactoryInterface
    {
        if (isset($this->transformationFactories[$transformationName])) {
            return $this->transformationFactories[$transformationName];
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

        throw new MigrationException(
            'The "' . $transformationName . '" transformation is not registered.',
            1659602562
        );
    }
}
