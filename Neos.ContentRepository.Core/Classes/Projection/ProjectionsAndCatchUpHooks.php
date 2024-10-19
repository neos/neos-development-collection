<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

/**
 * @internal
 */
final readonly class ProjectionsAndCatchUpHooks
{
    public Projections $projections;

    /**
     * @param array<class-string<ProjectionInterface<ProjectionStateInterface>>, CatchUpHookFactories> $catchUpHookFactoriesByProjectionClassName
     */
    public function __construct(
        public ContentRepositoryProjectionInterface $contentRepositoryProjection,
        Projections $additionalProjections,
        private array $catchUpHookFactoriesByProjectionClassName,
    ) {
        $this->projections = $additionalProjections->with($this->contentRepositoryProjection);
    }

    /**
     * @param ProjectionInterface<ProjectionStateInterface> $projection
     */
    public function getCatchUpHookFactoryForProjection(ProjectionInterface $projection): ?CatchUpHookFactoryInterface
    {
        return $this->catchUpHookFactoriesByProjectionClassName[$projection::class] ?? null;
    }
}
