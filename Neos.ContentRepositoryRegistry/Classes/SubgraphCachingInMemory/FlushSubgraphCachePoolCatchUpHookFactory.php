<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\SubgraphCachingInMemory;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;

/**
 * Factory for {@see FlushSubgraphCachePoolCatchUpHook}, auto-registered in Settings.yaml for GraphProjection
 *
 * @internal
 */
class FlushSubgraphCachePoolCatchUpHookFactory implements CatchUpHookFactoryInterface
{

    public function __construct(
        private readonly SubgraphCachePool $subgraphCachePool
    ) {
    }
    public function build(ContentRepository $contentRepository): CatchUpHookInterface
    {
        return new FlushSubgraphCachePoolCatchUpHook($this->subgraphCachePool);
    }
}
