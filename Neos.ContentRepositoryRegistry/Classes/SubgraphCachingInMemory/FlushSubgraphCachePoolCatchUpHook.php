<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\SubgraphCachingInMemory;

use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Ensures that the {@see SubgraphCachePool} is flushed always when content changes. This CatchUpHook
 * is triggered when projections change.
 *
 * Implementation note:
 * - We could flush the cache also on each 'onAfterEvent' but for that this catchup hook must be guaranteed to be invoked first, and currently there is no sorting for 'catchUpHooks'
 * - Also flushing in a catchup hook only works because this hook and the catchup is executed synchronously. A future async catchup must be flushed instead via a custom command hook instead
 *
 * @internal
 */
#[Flow\Proxy(false)]
final class FlushSubgraphCachePoolCatchUpHook implements CatchUpHookInterface
{

    public function __construct(private readonly SubgraphCachePool $subgraphCachePool)
    {
    }

    public function onBeforeCatchUp(SubscriptionStatus $subscriptionStatus): void
    {
        $this->subgraphCachePool->reset(disable: true);
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
    }

    public function onAfterBatchCompleted(): void
    {
    }

    public function onAfterCatchUp(): void
    {
        $this->subgraphCachePool->reset(disable: false);
    }
}
