<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

/**
 * @internal
 */
final readonly class ProjectionsAndCatchUpHooks
{
    /**
     * @param array<class-string<ProjectionInterface<ProjectionStateInterface>>, CatchUpHookFactories> $catchUpHookFactoriesByProjectionClassName
     */
    public function __construct(
        public ContentRepositoryReadModelProjection $readModelProjection,
        public Projections $additionalProjections,
        private array $catchUpHookFactoriesByProjectionClassName,
    ) {
    }

    /**
     * @param ProjectionInterface<ProjectionStateInterface> $projection
     */
    public function getCatchUpHookFactoryForProjection(ProjectionInterface $projection): ?CatchUpHookFactoryInterface
    {
        return $this->catchUpHookFactoriesByProjectionClassName[$projection::class] ?? null;
    }

    /**
     * @template T of ProjectionInterface
     * @param class-string<T> $projectionClassName
     * @return T
     */
    public function getProjection(string $projectionClassName): ProjectionInterface
    {
        if ($this->readModelProjection instanceof $projectionClassName) {
            return $this->readModelProjection;
        }
        return $this->additionalProjections->get($projectionClassName);
    }

    public function getAllProjections(): Projections
    {
        return $this->additionalProjections->with($this->readModelProjection);
    }
}
