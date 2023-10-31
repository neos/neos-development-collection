<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

/**
 * @internal
 */
final class ProjectionsAndCatchUpHooks
{
    /**
     * @param array<class-string<ProjectionInterface<ProjectionStateInterface>>, CatchUpHookFactories> $catchUpHookFactoriesByProjectionClassName
     */
    public function __construct(
        public readonly Projections $projections,
        private readonly array $catchUpHookFactoriesByProjectionClassName,
    ) {
    }

    /**
     * @param ProjectionInterface<ProjectionStateInterface> $projection
     */
    public function getCatchUpHookFactoryForProjection(ProjectionInterface $projection): ?CatchUpHookFactoryInterface
    {
        return $this->catchUpHookFactoriesByProjectionClassName[$projection::class] ?? null;
    }
}
