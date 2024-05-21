<?php

namespace Neos\ContentRepository\Core\Factory;

use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;

/**
 * @api for custom framework integrations, not for users of the CR
 */
final class ProjectionsFactory
{
    /**
     * @var array<array{factory: ProjectionFactoryInterface<ProjectionInterface<ProjectionStateInterface>>, options: array<string, mixed>}>
     */
    private array $factories = [];

    /**
     * @param ProjectionFactoryInterface<ProjectionInterface<ProjectionStateInterface>> $factory
     * @param array<string,mixed> $options
     * @return void
     * @api
     */
    public function registerFactory(ProjectionFactoryInterface $factory, array $options): void
    {
        $this->factories[] = [
            'factory' => $factory,
            'options' => $options,
        ];
    }

    /**
     * @internal this method is only called by the {@see ContentRepositoryBuilder}, and not by anybody in userland
     */
    public function build(ProjectionFactoryDependencies $projectionFactoryDependencies): Projections
    {
        $projectionsArray = [];
        foreach ($this->factories as $factoryDefinition) {
            $factory = $factoryDefinition['factory'];
            $options = $factoryDefinition['options'];
            assert($factory instanceof ProjectionFactoryInterface);
            $projection = $factory->build(
                $projectionFactoryDependencies,
                $options,
            );
            $projectionsArray[] = $projection;
        }
        return Projections::fromArray($projectionsArray);
    }
}
