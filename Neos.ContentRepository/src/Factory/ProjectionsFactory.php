<?php

namespace Neos\ContentRepository\Factory;

use Neos\ContentRepository\Projection\CatchUpHandlerFactories;
use Neos\ContentRepository\Projection\CatchUpHandlerFactoryInterface;
use Neos\ContentRepository\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Projection\Projections;

final class ProjectionsFactory
{

    private array $factories = [];

    public function registerFactory(ProjectionFactoryInterface $factory, array $options): void
    {
        $this->factories[get_class($factory)] = [
            'factory' => $factory,
            'options' => $options,
            'catchUpHandlers' => []
        ];
    }

    public function registerCatchUpHandlerFactory(ProjectionFactoryInterface $factory, CatchUpHandlerFactoryInterface $catchUpHandlerFactory, array $options): void
    {
        $this->factories[get_class($factory)]['catchUpHandlers'][] = [
            'catchUpHandlerFactory' => $catchUpHandlerFactory,
            'options' => $options,
        ];
    }

    public function build(ProjectionFactoryDependencies $projectionFactoryDependencies): Projections
    {
        $projections = Projections::create();
        foreach ($this->factories as $factoryDefinition) {
            $factory = $factoryDefinition['factory'];
            $options = $factoryDefinition['options'];
            assert($factory instanceof ProjectionFactoryInterface);

            $catchUpHandlerFactories = CatchUpHandlerFactories::create();
            foreach ($factoryDefinition['catchUpHandlers'] as $catchUpHandlerDefinition) {
                $catchUpHandlerFactory = $catchUpHandlerDefinition['catchUpHandlerFactory'];
                $catchUpHandlerOptions = $catchUpHandlerDefinition['options'];
                assert($catchUpHandlerFactory instanceof CatchUpHandlerFactoryInterface);
                $catchUpHandlerFactories = $catchUpHandlerFactories->with($catchUpHandlerFactory, $catchUpHandlerOptions);
            }

            $projections = $projections->with($factory->build($projectionFactoryDependencies, $options, $catchUpHandlerFactories, $projections));
        }

        return $projections;
    }
}
