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

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\EventStore\EventInterface;
use Neos\ContentRepository\Feature\Common\EmbedsContentStreamAndNodeAggregateIdentifier;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Projection\CatchUpHookInterface;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;

class GraphProjectorCatchUpHookForCacheFlushing implements CatchUpHookInterface
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ContentCacheFlusher $contentCacheFlusher
    ) {
    }


    public function onBeforeCatchUp(): void
    {
    }

    public function onBeforeEvent(EventInterface $eventInstance): void
    {
        //if ($doingFullReplayOfProjection) {
        // performance optimization: on full replay, we assume all caches to be flushed anyways
        // - so we do not need to do it individually here.
        //     return;
        //}
        if ($eventInstance instanceof NodeAggregateWasRemoved) {
            $nodeAggregate = $this->contentRepository->getContentGraph()->findNodeAggregateByIdentifier(
                $eventInstance->getContentStreamIdentifier(),
                $eventInstance->getNodeAggregateIdentifier()
            );
            if ($nodeAggregate) {
                $parentNodeAggregates = $this->contentRepository->getContentGraph()->findParentNodeAggregates(
                    $nodeAggregate->getContentStreamIdentifier(),
                    $nodeAggregate->getIdentifier()
                );
                foreach ($parentNodeAggregates as $parentNodeAggregate) {
                    assert($parentNodeAggregate instanceof NodeAggregate);
                    $this->scheduleCacheFlushJobForNodeAggregate(
                        $this->contentRepository,
                        $parentNodeAggregate->getContentStreamIdentifier(),
                        $parentNodeAggregate->getIdentifier()
                    );
                }
            }
        }
    }

    public function onAfterEvent(EventInterface $eventInstance): void
    {
        // TODO if ($doingFullReplayOfProjection) {
            // performance optimization: on full replay, we assume all caches to be flushed anyways
            // - so we do not need to do it individually here.
        //    return;
        //}

        if (
            !($eventInstance instanceof NodeAggregateWasRemoved)
            && $eventInstance instanceof EmbedsContentStreamAndNodeAggregateIdentifier
        ) {
            $nodeAggregate = $this->contentRepository->getContentGraph()->findNodeAggregateByIdentifier(
                $eventInstance->getContentStreamIdentifier(),
                $eventInstance->getNodeAggregateIdentifier()
            );

            if ($nodeAggregate) {
                $this->scheduleCacheFlushJobForNodeAggregate(
                    $this->contentRepository,
                    $nodeAggregate->getContentStreamIdentifier(),
                    $nodeAggregate->getIdentifier()
                );
            }
        }
    }
    /**
     * @var array<int,array<string,mixed>>
     */
    protected array $cacheFlushes = [];

    protected function scheduleCacheFlushJobForNodeAggregate(
        ContentRepository $contentRepository,
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): void {
        // we store this in an associative array deduplicate.
        $this->cacheFlushes[$contentStreamIdentifier->getValue() . '__' . $nodeAggregateIdentifier->getValue()] = [
            'cr' => $contentRepository,
            'csi' => $contentStreamIdentifier,
            'nai' => $nodeAggregateIdentifier
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
