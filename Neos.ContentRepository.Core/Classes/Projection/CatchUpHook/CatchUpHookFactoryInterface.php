<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\CatchUpHook;

use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;

/**
 * @template T of ProjectionStateInterface
 * @api
 */
interface CatchUpHookFactoryInterface
{
    /**
     * Note that a catchup doesn't have access to the full content repository, as it would allow full recursion via handle and accessing other projections
     * state is not safe as the other projection might not be behind - the order is undefined.
     *
     * @param CatchUpHookFactoryDependencies<T> $dependencies available dependencies to intialise the catchup hook
     * @return CatchUpHookInterface
     */
    public function build(CatchUpHookFactoryDependencies $dependencies): CatchUpHookInterface;
}
