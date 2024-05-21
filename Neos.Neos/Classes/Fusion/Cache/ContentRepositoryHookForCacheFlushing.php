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
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryHookInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Also contains a pragmatic performance booster for some "batch" operations, where the cache flushing
 * is not needed:
 *
 * By calling {@see self::disabled(\Closure)} in your code, all projection updates
 * will never trigger catch up hooks.
 *
 *
 * The following scenario explains how to think about cache flushing.
 *
 * TODO: Overhaul and adjust to new architecture
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
class ContentRepositoryHookForCacheFlushing implements ContentRepositoryHookInterface
{
    private static bool $enabled = true;

    /**
     * @var array<string,array<string,mixed>>
     */
    private array $cacheFlushesOnAfterCatchUp = [];


    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ContentCacheFlusher $contentCacheFlusher
    ) {
    }

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


    public function onBeforeEvents(): void
    {
    }

    public function onBeforeEvent(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        if (!self::$enabled) {
            return;
        }

        if (
            !$event instanceof NodeAggregateWasRemoved
            // NOTE: when moving a node, we need to clear the cache not just after the move was completed,
            // but also on the original location. Otherwise, we have the problem that the cache is not
            // cleared, leading to presumably duplicate nodes in the UI.
            && !$event instanceof NodeAggregateWasMoved
        ) {
            return;
        }
        $nodeAggregate = $this->contentRepository->getContentGraph()->findNodeAggregateById($event->getContentStreamId(), $event->getNodeAggregateId());
        if ($nodeAggregate === null) {
            return;
        }
        $parentNodeAggregates = $this->contentRepository->getContentGraph()->findParentNodeAggregates($nodeAggregate->contentStreamId, $nodeAggregate->nodeAggregateId);
        foreach ($parentNodeAggregates as $parentNodeAggregate) {
            assert($parentNodeAggregate instanceof NodeAggregate);
            $this->scheduleCacheFlushJobForNodeAggregate($this->contentRepository, $parentNodeAggregate->contentStreamId, $parentNodeAggregate->nodeAggregateId);
        }
    }

    public function onAfterEvent(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        if (!self::$enabled) {
            return;
        }
        if (
            $event instanceof NodeAggregateWasRemoved
            || !$event instanceof EmbedsContentStreamAndNodeAggregateId
        ) {
            return;
        }
        $nodeAggregate = $this->contentRepository->getContentGraph()->findNodeAggregateById($event->getContentStreamId(), $event->getNodeAggregateId());
        if ($nodeAggregate !== null) {
            $this->scheduleCacheFlushJobForNodeAggregate($this->contentRepository, $nodeAggregate->contentStreamId, $nodeAggregate->nodeAggregateId);
        }
    }

    public function onAfterEvents(): void
    {
        foreach ($this->cacheFlushesOnAfterCatchUp as $entry) {
            $this->contentCacheFlusher->flushNodeAggregate($entry['cr'], $entry['csi'], $entry['nai']);
        }
        $this->cacheFlushesOnAfterCatchUp = [];
    }

    private function scheduleCacheFlushJobForNodeAggregate(ContentRepository $contentRepository, ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId): void
    {
        // we store this in an associative array deduplicate.
        $this->cacheFlushesOnAfterCatchUp[$contentStreamId->value . '__' . $nodeAggregateId->value] = [
            'cr' => $contentRepository,
            'csi' => $contentStreamId,
            'nai' => $nodeAggregateId
        ];
    }
}
