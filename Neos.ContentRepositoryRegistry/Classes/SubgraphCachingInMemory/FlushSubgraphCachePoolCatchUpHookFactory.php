<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\SubgraphCachingInMemory;

use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryDependencies;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;

/**
 * Factory for {@see FlushSubgraphCachePoolCatchUpHook}, auto-registered in Settings.yaml for GraphProjection
 *
 * @implements CatchUpHookFactoryInterface<ContentGraphReadModelInterface>
 * @internal
 */
class FlushSubgraphCachePoolCatchUpHookFactory implements CatchUpHookFactoryInterface
{

    public function __construct(
        private readonly SubgraphCachePool $subgraphCachePool
    ) {
    }

    public function build(CatchUpHookFactoryDependencies $dependencies): CatchUpHookInterface
    {
        return new FlushSubgraphCachePoolCatchUpHook($this->subgraphCachePool);
    }
}
