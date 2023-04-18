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
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Also contains a pragmatic performance booster for some "batch" operations, where the cache flushing
 * is not needed:
 *
 * By calling {@see self::disabled(\Closure)} in your code, all projection updates
 * will never trigger catch up hooks.
 *
 * NOTE: This will only work when {@see CatchUpTriggerWithSynchronousOption::synchronously()} is called,
 * as otherwise this subprocess won't be called.
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

        if ($eventInstance instanceof NodeAggregateWasRemoved) {
            $nodeAggregate = $this->contentRepository->getContentGraph()->findNodeAggregateById(
                $eventInstance->getContentStreamId(),
                $eventInstance->getNodeAggregateId()
            );
            if ($nodeAggregate) {
                $parentNodeAggregates = $this->contentRepository->getContentGraph()->findParentNodeAggregates(
                    $nodeAggregate->contentStreamId,
                    $nodeAggregate->nodeAggregateId
                );
                foreach ($parentNodeAggregates as $parentNodeAggregate) {
                    assert($parentNodeAggregate instanceof NodeAggregate);
                    $this->scheduleCacheFlushJobForNodeAggregate(
                        $this->contentRepository,
                        $parentNodeAggregate->contentStreamId,
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
            && $eventInstance instanceof EmbedsContentStreamAndNodeAggregateId
        ) {
            $nodeAggregate = $this->contentRepository->getContentGraph()->findNodeAggregateById(
                $eventInstance->getContentStreamId(),
                $eventInstance->getNodeAggregateId()
            );

            if ($nodeAggregate) {
                $this->scheduleCacheFlushJobForNodeAggregate(
                    $this->contentRepository,
                    $nodeAggregate->contentStreamId,
                    $nodeAggregate->nodeAggregateId
                );
            }
        }
    }
    /**
     * @var array<string,array<string,mixed>>
     */
    protected array $cacheFlushes = [];

    protected function scheduleCacheFlushJobForNodeAggregate(
        ContentRepository $contentRepository,
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): void {
        // we store this in an associative array deduplicate.
        $this->cacheFlushes[$contentStreamId->value . '__' . $nodeAggregateId->value] = [
            'cr' => $contentRepository,
            'csi' => $contentStreamId,
            'nai' => $nodeAggregateId
        ];
    }

    public function onBeforeBatchCompleted(): void
    {
        foreach ($this->cacheFlushes as $entry) {
            $this->contentCacheFlusher->flushNodeAggregate($entry['cr'], $entry['csi'], $entry['nai']);
        }
        $this->cacheFlushes = [];
    }



    public function onAfterCatchUp(): void
    {
    }
}
