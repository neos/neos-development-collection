<?php

declare(strict_types=1);

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
use Neos\ContentRepository\Core\Feature\Common\EmbedsNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsWorkspaceName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateDimensionsWereUpdated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
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
 *                                                  projection update
 *                                                   call finished
 *     EventStore::commit
 *              ║                                        │
 *         ─────╬──────────────────────────!1!───────────┼────────!2!─▶
 *              ║                                       ▲│
 *               │                                      │
 *               │                                      │                NO async boundary anymore!
 *               │                                      │                 => we can GUARANTEE that
 *               │                                      │                  onAfterCatchUp has run
 *               │                                      │   SYNC         before control is returned
 *               │                                      │  POINT               to the caller.
 *               │                             ║        │
 *  Projection::catchUp    │    │              ║       ││
 *         ────────────────┼────┼──────────────╬───────┼──────────────▶
 *                         │    │              ║       │
 *           update Projection  │              ║       │
 *          state (old -> new)  │              ║       │
 *                              │           TX commit  │
 *                      update sequence  (end of batch)│
 *                          number                     │
 *                                                     │
 *                                               onAfterCatchUp
 *                                                => e.g. flush
 *                                                Fusion cache
 *
 * @internal
 */
class GraphProjectorCatchUpHookForCacheFlushing implements CatchUpHookInterface
{
    private static bool $enabled = true;

    /**
     * @var array<string,FlushNodeAggregateRequest>
     */
    private array $flushNodeAggregateRequestsOnAfterCatchUp = [];

    /**
     * @var array<string,FlushWorkspaceRequest>
     */
    private array $flushWorkspaceRequestsOnAfterCatchUp = [];

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

    public function canHandle(EventInterface $event): bool
    {
        return in_array($event::class, [
            NodeAggregateNameWasChanged::class,
            NodeAggregateTypeWasChanged::class,
            NodeAggregateWasMoved::class,
            NodeAggregateWasRemoved::class,
            NodeAggregateWithNodeWasCreated::class,
            NodeGeneralizationVariantWasCreated::class,
            NodePeerVariantWasCreated::class,
            NodePropertiesWereSet::class,
            NodeReferencesWereSet::class,
            NodeSpecializationVariantWasCreated::class,
            RootNodeAggregateDimensionsWereUpdated::class,
            RootNodeAggregateWithNodeWasCreated::class,
            SubtreeWasTagged::class,
            SubtreeWasUntagged::class,
            WorkspaceWasDiscarded::class,
            WorkspaceWasPartiallyDiscarded::class,
            WorkspaceWasRebased::class
        ]);
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

        if (!$this->canHandle($eventInstance)) {
            return;
        }

        if (
            $eventInstance instanceof NodeAggregateWasRemoved
            // NOTE: when moving a node, we need to clear the cache not just after the move was completed,
            // but also on the original location. Otherwise, we have the problem that the cache is not
            // cleared, leading to presumably duplicate nodes in the UI.
            || $eventInstance instanceof NodeAggregateWasMoved
        ) {
            $contentGraph = $this->contentRepository->getContentGraph($eventInstance->workspaceName);
            $nodeAggregate = $contentGraph->findNodeAggregateById(
                $eventInstance->getNodeAggregateId()
            );
            if ($nodeAggregate) {
                $this->scheduleCacheFlushJobForNodeAggregate(
                    $this->contentRepository,
                    $nodeAggregate,
                    $contentGraph->findAncestorNodeAggregateIds($eventInstance->getNodeAggregateId()),
                );
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

        if (!$this->canHandle($eventInstance)) {
            return;
        }

        if (
            $eventInstance instanceof WorkspaceWasDiscarded
            || $eventInstance instanceof WorkspaceWasPartiallyDiscarded
            || $eventInstance instanceof WorkspaceWasRebased
        ) {
            $this->scheduleCacheFlushJobForWorkspaceName($this->contentRepository, $eventInstance->workspaceName);
        } elseif (
            !($eventInstance instanceof NodeAggregateWasRemoved)
            && $eventInstance instanceof EmbedsNodeAggregateId
            && $eventInstance instanceof EmbedsWorkspaceName
        ) {
            $contentGraph = $this->contentRepository->getContentGraph($eventInstance->getWorkspaceName());
            $nodeAggregate = $contentGraph->findNodeAggregateById(
                $eventInstance->getNodeAggregateId()
            );

            if ($nodeAggregate) {
                $this->scheduleCacheFlushJobForNodeAggregate(
                    $this->contentRepository,
                    $nodeAggregate,
                    $contentGraph->findAncestorNodeAggregateIds($eventInstance->getNodeAggregateId())
                );
            }
        }
    }

    private function scheduleCacheFlushJobForNodeAggregate(
        ContentRepository $contentRepository,
        NodeAggregate $nodeAggregate,
        NodeAggregateIds $ancestorNodeAggregateIds
    ): void {
        // we store this in an associative array deduplicate.
        $this->flushNodeAggregateRequestsOnAfterCatchUp[$nodeAggregate->workspaceName->value . '__' . $nodeAggregate->nodeAggregateId->value] = FlushNodeAggregateRequest::create(
            $contentRepository->id,
            $nodeAggregate->workspaceName,
            $nodeAggregate->nodeAggregateId,
            $nodeAggregate->nodeTypeName,
            $ancestorNodeAggregateIds
        );
    }

    private function scheduleCacheFlushJobForWorkspaceName(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName
    ): void {
        // we store this in an associative array deduplicate.
        $this->flushWorkspaceRequestsOnAfterCatchUp[$workspaceName->value] = FlushWorkspaceRequest::create(
            $contentRepository->id,
            $workspaceName,
        );
    }

    public function onBeforeBatchCompleted(): void
    {
    }

    public function onAfterCatchUp(): void
    {
        foreach ($this->flushNodeAggregateRequestsOnAfterCatchUp as $request) {
            // We do not need to flush single node aggregates if we flush the whole workspace anyway.
            if (!isset($this->flushWorkspaceRequestsOnAfterCatchUp[$request->workspaceName->value])) {
                $this->contentCacheFlusher->flushNodeAggregate($request, CacheFlushingStrategy::IMMEDIATE);
            }
        }
        $this->flushNodeAggregateRequestsOnAfterCatchUp = [];

        foreach ($this->flushWorkspaceRequestsOnAfterCatchUp as $request) {
            $this->contentCacheFlusher->flushWorkspace($request, CacheFlushingStrategy::IMMEDIATE);
        }
        $this->flushWorkspaceRequestsOnAfterCatchUp = [];
    }
}
