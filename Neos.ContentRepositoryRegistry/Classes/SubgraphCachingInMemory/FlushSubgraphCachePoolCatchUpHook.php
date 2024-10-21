<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\SubgraphCachingInMemory;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Ensures that the {@see SubgraphCachePool} is flushed always when content changes. This CatchUpHook
 * is triggered when projections change.
 *
 * @internal
 */
#[Flow\Proxy(false)]
final class FlushSubgraphCachePoolCatchUpHook implements CatchUpHookInterface
{

    public function __construct(private readonly SubgraphCachePool $subgraphCachePool)
    {
    }

    public function onBeforeCatchUp(): void
    {
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        $this->subgraphCachePool->reset();
    }

    public function onBeforeBatchCompleted(): void
    {
    }

    public function onAfterCatchUp(): void
    {
        $this->subgraphCachePool->reset();
    }
}
