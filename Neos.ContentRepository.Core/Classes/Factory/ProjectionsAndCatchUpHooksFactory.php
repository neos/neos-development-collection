<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Factory;

use Neos\ContentRepository\Core\Projection\CatchUpHookFactories;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\ProjectionsAndCatchUpHooks;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;

/**
 * @api for custom framework integrations, not for users of the CR
 */
final class ProjectionsAndCatchUpHooksFactory
{
    /**
     * @var array<string, array{factory: ProjectionFactoryInterface<ProjectionInterface<ProjectionStateInterface>>, options: array<string, mixed>, catchUpHooksFactories: array<CatchUpHookFactoryInterface>}>
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
            'catchUpHooksFactories' => []
        ];
    }

    /**
     * @param ProjectionFactoryInterface<ProjectionInterface<ProjectionStateInterface>> $factory
     * @param CatchUpHookFactoryInterface $catchUpHookFactory
     * @return void
     * @api
     */
    public function registerCatchUpHookFactory(ProjectionFactoryInterface $factory, CatchUpHookFactoryInterface $catchUpHookFactory): void
    {
        $this->factories[get_class($factory)]['catchUpHooksFactories'][] = $catchUpHookFactory;
    }

    /**
     * @internal this method is only called by the {@see ContentRepositoryFactory}, and not by anybody in userland
     */
    public function build(ProjectionFactoryDependencies $projectionFactoryDependencies): ProjectionsAndCatchUpHooks
    {
        $projectionsArray = [];
        $catchUpHookFactoriesByProjectionClassName = [];
        foreach ($this->factories as $factoryDefinition) {
            $factory = $factoryDefinition['factory'];
            $options = $factoryDefinition['options'];
            assert($factory instanceof ProjectionFactoryInterface);

            $catchUpHookFactories = CatchUpHookFactories::create();
            foreach ($factoryDefinition['catchUpHooksFactories'] as $catchUpHookFactory) {
                assert($catchUpHookFactory instanceof CatchUpHookFactoryInterface);
                $catchUpHookFactories = $catchUpHookFactories->with($catchUpHookFactory);
            }

            $projection = $factory->build(
                $projectionFactoryDependencies,
                $options,
            );
            $catchUpHookFactoriesByProjectionClassName[$projection::class] = $catchUpHookFactories;
            $projectionsArray[] = $projection;
        }

        return new ProjectionsAndCatchUpHooks(Projections::fromArray($projectionsArray), $catchUpHookFactoriesByProjectionClassName);
    }
}
