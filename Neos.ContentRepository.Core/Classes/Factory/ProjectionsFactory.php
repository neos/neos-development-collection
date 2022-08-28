<?php

namespace Neos\ContentRepository\Factory;

use Neos\ContentRepository\Projection\CatchUpHookFactories;
use Neos\ContentRepository\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\ContentRepository\Projection\Projections;
use Neos\ContentRepository\Projection\ProjectionStateInterface;

/**
 * @api
 */
final class ProjectionsFactory
{
    /**
     * @phpstan-ignore-next-line
     * @var array
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
        $this->factories[get_class($factory)] = [
            'factory' => $factory,
            'options' => $options,
            'catchUpHooks' => []
        ];
    }

    /**
     * @param ProjectionFactoryInterface<ProjectionInterface<ProjectionStateInterface>> $factory
     * @param CatchUpHookFactoryInterface $catchUpHookFactory
     * @return void
     * @api
     */
    public function registerCatchUpHookFactory(
        ProjectionFactoryInterface $factory,
        CatchUpHookFactoryInterface $catchUpHookFactory
    ): void {
        $this->factories[get_class($factory)]['catchUpHooks'][] = [
            'catchUpHookFactory' => $catchUpHookFactory,
        ];
    }

    /**
     * @internal this method is only called by the {@see ContentRepositoryFactory}, and not by anybody in userland
     */
    public function build(ProjectionFactoryDependencies $projectionFactoryDependencies): Projections
    {
        $projections = Projections::create();
        foreach ($this->factories as $factoryDefinition) {
            $factory = $factoryDefinition['factory'];
            $options = $factoryDefinition['options'];
            assert($factory instanceof ProjectionFactoryInterface);

            $catchUpHookFactories = CatchUpHookFactories::create();
            foreach ($factoryDefinition['catchUpHooks'] as $catchUpHookDefinition) {
                $catchUpHookFactory = $catchUpHookDefinition['catchUpHookFactory'];
                assert($catchUpHookFactory instanceof CatchUpHookFactoryInterface);
                $catchUpHookFactories = $catchUpHookFactories->with($catchUpHookFactory);
            }

            $projections = $projections->with($factory->build(
                $projectionFactoryDependencies,
                $options,
                $catchUpHookFactories,
                $projections
            ));
        }

        return $projections;
    }
}
