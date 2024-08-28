<?php

namespace Neos\Neos\Fusion\Cache;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsNodeAggregateId;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Also contains a pragmatic performance booster for some "batch" operations, where the cache flushing
 * is not needed:
 *
 * By calling {@see self::disabled(\Closure)} in your code, all projection updates
 * will never trigger catch up hooks. This will only work when
 * {@see CatchUpTriggerWithSynchronousOption::synchronously()} is called,
 * as otherwise this subprocess won't be called.
 *
 *
 * The following scenario explains how to think about cache flushing.
 *
 *       EventStore::commit                         block() finished
 *               ║                                        │
 *          ─────╬──────────────────────────!1!───────────┼─!2!────────▶
 *              SYNC POINT                               ▲│
 *                ╲                                     ╱
 *                 ╲                                   ╱
 *                  ╲                                 ╱
 *                   ╲                               ╱
 *     ─ ─ ─ ─ ─ ─ ─ ─╲─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─╱─ ─ ─ ─ ─ ─ ─ ─ ─ ─ async boundary
 *                     ╲                         SYNC POINT
 *                       ╲                      ║ ╱
 *     Projection::catchUp▼ │    │        │     ║╱              │
 *          ────────────────┼────┼────────┼─────╳───────────────┼──────▶
 *                          │    │        │     ║               │
 *            update Projection  │        │     ║               │
 *           state (old -> new)  │        │     TX commit       │
 *                               │        │  (end of batch)     │
 *                       update sequence  │                     │
 *                           number       │                     │
 *                                        │                     │
 *                                                           onAfterCatchUp
 *                                    onBefore                 (B)
 *                                 BatchCompleted
 *                                       (A)
 *
 * There are two natural places where the Fusion cache can be flushed:
 * - A: onBeforeBatchCompleted (before ending the transaction in the projection)
 * - B: onAfterCatchUp (after ending the transaction).
 *
 * We need to ensure that the system is eventually consistent, so the following invariants must hold:
 * - After a change in the projection, some time later, the cache must have been flushed
 * - at a re-rendering after the cache flush, the new content must be shown
 * - when block() returns, ANY re-render (even if happening immediately) must return the new content.
 * - (Eventual Consistency): Processes can be blocked arbitrarily long at any point in time indefinitely.
 *
 * The scenarios which are NOT allowed to happen are:
 * - INVARIANT_1: after a change, the old content is still visible when all processes have ended.
 * - INVARIANT_2: after a change, when rendering happens directly after block(), the old content
 *   is shown (e.g. because cache is not yet flushed).
 *
 * CASE A (cache flushed at onBeforeBatchCompleted only):
 * - Let's assume the cache is flushed really quickly.
 * - and AFTER the cache is flushed but BEFORE the transaction is committed,
 * - another request hits the system - marked above with !1!
 *
 * THEN: the request will still load the old data, render the page based on the old data, and add
 * the old data to the cache. The cache will not be flushed again because it has already been flushed.
 *
 * => INVARIANT_1 violated.
 * => this case needs a cache flush at onAfterCatchUp; to ensure the system converges.
 *
 * CASE B (cache flushed on onAfterCatchUp only):
 * - Let's assume the blocking has finished, and caches have not been flushed yet.
 * - Then, during re-rendering, the old content is shown because the cache is still full
 *
 * => INVARIANT_2 violated.
 * => this case needs a cache flush at onBeforeBatchCompleted.
 *
 * SUMMARY: we need to flush the cache at BOTH places.
 *
 * @internal
 */
class GraphProjectorCatchUpHookForCacheFlushing implements CatchUpHookInterface
{
    private static bool $enabled = true;


    public static function disabled(\Closure $fn): void
    {
        $previousValue = self::$enabled;
        self::$enabled = false;
        try {
            $fn();
        } finally {
            self::$enabled = $previousValue;
        }
    }


    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ContentCacheFlusher $contentCacheFlusher
    ) {
    }


    public function onBeforeCatchUp(): void
    {
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        if (!self::$enabled) {
            // performance optimization: on full replay, we assume all caches to be flushed anyways
            // - so we do not need to do it individually here.
            return;
        }

        if (
            $eventInstance instanceof NodeAggregateWasRemoved
            // NOTE: when moving a node, we need to clear the cache not just after the move was completed,
            // but also on the original location. Otherwise, we have the problem that the cache is not
            // cleared, leading to presumably duplicate nodes in the UI.
            || $eventInstance instanceof NodeAggregateWasMoved
        ) {
            $workspace = $this->contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId($eventInstance->getContentStreamId());
            if ($workspace === null) {
                return;
            }
            // FIXME: EventInterface->workspaceName
            $contentGraph = $this->contentRepository->getContentGraph($workspace->workspaceName);
            $nodeAggregate = $contentGraph->findNodeAggregateById(
                $eventInstance->getNodeAggregateId()
            );
            if ($nodeAggregate) {
                $parentNodeAggregates = $contentGraph->findParentNodeAggregates(
                    $nodeAggregate->nodeAggregateId
                );
                foreach ($parentNodeAggregates as $parentNodeAggregate) {
                    assert($parentNodeAggregate instanceof NodeAggregate);
                    $this->scheduleCacheFlushJobForNodeAggregate(
                        $this->contentRepository,
                        $workspace->workspaceName,
                        $parentNodeAggregate->nodeAggregateId
                    );
                }
            }
        }
    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        if (!self::$enabled) {
            // performance optimization: on full replay, we assume all caches to be flushed anyways
            // - so we do not need to do it individually here.
            return;
        }

        if (
            !($eventInstance instanceof NodeAggregateWasRemoved)
            && $eventInstance instanceof EmbedsNodeAggregateId
            && $eventInstance instanceof EmbedsContentStreamId
        ) {
            $workspace = $this->contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId($eventInstance->getContentStreamId());
            if ($workspace === null) {
                return;
            }
            // FIXME: EventInterface->workspaceName
            $nodeAggregate = $this->contentRepository->getContentGraph($workspace->workspaceName)->findNodeAggregateById(
                $eventInstance->getNodeAggregateId()
            );

            if ($nodeAggregate) {
                $this->scheduleCacheFlushJobForNodeAggregate(
                    $this->contentRepository,
                    $workspace->workspaceName,
                    $nodeAggregate->nodeAggregateId
                );
            }
        }
    }

    /**
     * @var array<string,array<string,mixed>>
     */
    private array $cacheFlushesOnBeforeBatchCompleted = [];
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $cacheFlushesOnAfterCatchUp = [];

    protected function scheduleCacheFlushJobForNodeAggregate(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId
    ): void {
        // we store this in an associative array deduplicate.
        $this->cacheFlushesOnBeforeBatchCompleted[$workspaceName->value . '__' . $nodeAggregateId->value] = [
            'cr' => $contentRepository,
            'wsn' => $workspaceName,
            'nai' => $nodeAggregateId
        ];
    }

    public function onBeforeBatchCompleted(): void
    {
        foreach ($this->cacheFlushesOnBeforeBatchCompleted as $k => $entry) {
            $this->contentCacheFlusher->flushNodeAggregate($entry['cr'], $entry['wsn'], $entry['nai']);
            $this->cacheFlushesOnAfterCatchUp[$k] = $entry;
        }
        $this->cacheFlushesOnBeforeBatchCompleted = [];
    }


    public function onAfterCatchUp(): void
    {
        foreach ($this->cacheFlushesOnAfterCatchUp as $entry) {
            $this->contentCacheFlusher->flushNodeAggregate($entry['cr'], $entry['wsn'], $entry['nai']);
        }
        $this->cacheFlushesOnAfterCatchUp = [];
    }
}
