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
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Also contains a pragmatic performance booster for some "batch" operations, where the cache flushing
 * is not needed:
 *
 * By calling {@see self::disabled(\Closure)} in your code, all projection updates
 * will never trigger catch up hooks.
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
    private bool $isBooting = false;

    /**
     * @var array<string,FlushNodeAggregateRequest>
     */
    private array $flushNodeAggregateRequestsOnAfterCatchUp = [];

    /**
     * @var array<string,FlushWorkspaceRequest>
     */
    private array $flushWorkspaceRequestsOnAfterCatchUp = [];

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly ContentGraphReadModelInterface $contentGraphReadModel,
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
            WorkspaceWasRebased::class
        ]);
    }

    public function onBeforeCatchUp(SubscriptionStatus $subscriptionStatus): void
    {
        $this->isBooting = $subscriptionStatus == SubscriptionStatus::BOOTING;
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        if (!$this->canHandle($eventInstance)) {
            return;
        }

        // performance optimization: on full replay, we collect workspaces to flush after catch up
        if ($this->isBooting && $eventInstance instanceof EmbedsWorkspaceName) {
            $this->scheduleCacheFlushJobForWorkspaceName($eventInstance->getWorkspaceName());
            return;
        }

        if (
            $eventInstance instanceof NodeAggregateWasRemoved
            // NOTE: when moving a node, we need to clear the cache not just after the move was completed,
            // but also on the original location. Otherwise, we have the problem that the cache is not
            // cleared, leading to presumably duplicate nodes in the UI.
            || $eventInstance instanceof NodeAggregateWasMoved
        ) {
            $contentGraph = $this->contentGraphReadModel->getContentGraph($eventInstance->workspaceName);
            $nodeAggregate = $contentGraph->findNodeAggregateById(
                $eventInstance->getNodeAggregateId()
            );
            if ($nodeAggregate) {
                $this->scheduleCacheFlushJobForNodeAggregate(
                    $nodeAggregate,
                    $contentGraph->findAncestorNodeAggregateIds($eventInstance->getNodeAggregateId()),
                );
            }
        }
    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        if (!$this->canHandle($eventInstance)) {
            return;
        }

        // performance optimization: on full replay, we collect workspaces to flush after catch up
        if ($this->isBooting && $eventInstance instanceof EmbedsWorkspaceName) {
            $this->scheduleCacheFlushJobForWorkspaceName($eventInstance->getWorkspaceName());
            return;
        }

        if (
            $eventInstance instanceof WorkspaceWasDiscarded
            || $eventInstance instanceof WorkspaceWasRebased
        ) {
            $this->scheduleCacheFlushJobForWorkspaceName($eventInstance->workspaceName);
        } elseif (
            !($eventInstance instanceof NodeAggregateWasRemoved)
            && $eventInstance instanceof EmbedsNodeAggregateId
            && $eventInstance instanceof EmbedsWorkspaceName
        ) {
            $contentGraph = $this->contentGraphReadModel->getContentGraph($eventInstance->getWorkspaceName());
            $nodeAggregate = $contentGraph->findNodeAggregateById(
                $eventInstance->getNodeAggregateId()
            );

            if ($nodeAggregate) {
                $this->scheduleCacheFlushJobForNodeAggregate(
                    $nodeAggregate,
                    $contentGraph->findAncestorNodeAggregateIds($eventInstance->getNodeAggregateId())
                );
            }
        }
    }

    private function scheduleCacheFlushJobForNodeAggregate(
        NodeAggregate $nodeAggregate,
        NodeAggregateIds $ancestorNodeAggregateIds
    ): void {

        $key = $nodeAggregate->workspaceName->value . '__' . $nodeAggregate->nodeAggregateId->value . '__' . $nodeAggregate->nodeTypeName->value;
        if (!isset($this->flushWorkspaceRequestsOnAfterCatchUp[$key])) {
            $this->flushNodeAggregateRequestsOnAfterCatchUp[$key] = FlushNodeAggregateRequest::create(
                $this->contentRepositoryId,
                $nodeAggregate->workspaceName,
                $nodeAggregate->nodeAggregateId,
                $nodeAggregate->nodeTypeName,
                $ancestorNodeAggregateIds
            );
        }
    }

    private function scheduleCacheFlushJobForWorkspaceName(
        WorkspaceName $workspaceName
    ): void {
        if (!isset($this->flushWorkspaceRequestsOnAfterCatchUp[$workspaceName->value])) {
            $this->flushWorkspaceRequestsOnAfterCatchUp[$workspaceName->value] = FlushWorkspaceRequest::create(
                $this->contentRepositoryId,
                $workspaceName,
            );
        }
    }

    public function onAfterBatchCompleted(): void
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
