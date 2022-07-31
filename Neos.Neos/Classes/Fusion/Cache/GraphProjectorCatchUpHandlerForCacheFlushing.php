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

use Flowpack\JobQueue\Common\Job\JobManager;
use Neos\ContentRepository\EventStore\EventInterface;
use Neos\ContentRepository\Feature\Common\EmbedsContentStreamAndNodeAggregateIdentifier;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Projection\CatchUpHandlerInterface;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;
use Neos\Flow\Annotations as Flow;


class GraphProjectorCatchUpHandlerForCacheFlushing implements CatchUpHandlerInterface
{

    /**
     * @Flow\Inject
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @Flow\InjectConfiguration("contentCacheFlusher.queueName")
     * @var string
     */
    protected $queueName;

    public function __construct(
        private readonly ContentGraphInterface $contentGraph
    )
    {
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
            $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier(
                $eventInstance->getContentStreamIdentifier(),
                $eventInstance->getNodeAggregateIdentifier()
            );
            if ($nodeAggregate) {
                $parentNodeAggregates = $this->contentGraph->findParentNodeAggregates(
                    $nodeAggregate->getContentStreamIdentifier(),
                    $nodeAggregate->getIdentifier()
                );
                foreach ($parentNodeAggregates as $parentNodeAggregate) {
                    assert($parentNodeAggregate instanceof NodeAggregate);
                    $this->scheduleCacheFlushJobForNodeAggregate(
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
            $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier(
                $eventInstance->getContentStreamIdentifier(),
                $eventInstance->getNodeAggregateIdentifier()
            );

            if ($nodeAggregate) {
                $this->scheduleCacheFlushJobForNodeAggregate(
                    $nodeAggregate->getContentStreamIdentifier(),
                    $nodeAggregate->getIdentifier()
                );
            }
        }
    }

    public function onAfterCatchUp(): void
    {
        $this->flushCache();
    }


    /**
     * @var array<int,array<string,mixed>>
     */
    protected array $cacheFlushes = [];

    protected function scheduleCacheFlushJobForNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): void {
        $this->cacheFlushes[] = [
            'csi' => $contentStreamIdentifier,
            'nai' => $nodeAggregateIdentifier
        ];
        if (count($this->cacheFlushes) === 20) {
            $this->flushCache();
        }
    }

    protected function flushCache(): void
    {
        $this->jobManager->queue($this->queueName, new CacheFlushJob($this->cacheFlushes));
        $this->cacheFlushes = [];
    }
}
