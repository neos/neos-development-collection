<?php

namespace Neos\ContentRepository\Factory;

use Neos\ContentRepository\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Projection\Projections;

final class ProjectionsFactory
{

    private array $factories = [];

    public function registerFactory(ProjectionFactoryInterface $factory, array $options): void
    {
        $this->factories[] = [
            'factory' => $factory,
            'options' => $options
        ];
    }

    public function build(ProjectionFactoryDependencies $projectionFactoryDependencies): Projections
    {
        $projections = Projections::create();
        foreach ($this->factories as $factoryDefinition) {
            $factory = $factoryDefinition['factory'];
            $options = $factoryDefinition['options'];
            assert($factory instanceof ProjectionFactoryInterface);

            $projections = $projections->with($factory->build($projectionFactoryDependencies, $options, $projections));
        }

        return $projections;
    }
}
