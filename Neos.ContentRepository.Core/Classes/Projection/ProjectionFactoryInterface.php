<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;

/**
 * @template T of ProjectionInterface
 * @api
 */
interface ProjectionFactoryInterface
{
    /**
     * @param array<string,mixed> $options
     * @return T
     */
    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
        CatchUpHookFactoryInterface $catchUpHookFactory,
        Projections $projectionsSoFar
    ): ProjectionInterface;
}
