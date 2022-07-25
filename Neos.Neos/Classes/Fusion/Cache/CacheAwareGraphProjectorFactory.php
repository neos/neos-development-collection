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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\GraphProjector;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Feature\Common\EmbedsContentStreamAndNodeAggregateIdentifier;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;

/**
 * This class is used as factory for {@see GraphProjector}, wired in Objects.yaml.
 *
 * It adds hooks to the GraphProjector which flush the caches when the projection changes. This is especially
 * needed during publishing, to ensure that the content caches are properly flushed.
 *
 * @Flow\Scope("singleton")
 */
class CacheAwareGraphProjectorFactory
{
    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

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

    public function build(
        DbalClientInterface $eventStorageDatabaseClient,
        VariableFrontend $processedEventsCache,
        ProjectionContentGraph $projectionContentGraph,
        ThrowableStorageInterface $throwableStorageInterface
    ): GraphProjector {
        $graphProjector = new GraphProjection(
            $eventStorageDatabaseClient,
            $processedEventsCache,
            $projectionContentGraph,
            $throwableStorageInterface
        );
        $graphProjector->onBeforeInvoke(function (EventEnvelope $eventEnvelope, bool $doingFullReplayOfProjection) {
            if ($doingFullReplayOfProjection) {
                // performance optimization: on full replay, we assume all caches to be flushed anyways
                // - so we do not need to do it individually here.
                return;
            }

            $domainEvent = $eventEnvelope->getDomainEvent();
            if ($domainEvent instanceof NodeAggregateWasRemoved) {
                $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier(
                    $domainEvent->getContentStreamIdentifier(),
                    $domainEvent->getNodeAggregateIdentifier()
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
        });
        $graphProjector->onAfterInvoke(function (EventEnvelope $eventEnvelope, bool $doingFullReplayOfProjection) {
            if ($doingFullReplayOfProjection) {
                // performance optimization: on full replay, we assume all caches to be flushed anyways
                // - so we do not need to do it individually here.
                return;
            }

            $domainEvent = $eventEnvelope->getDomainEvent();
            if (
                !($domainEvent instanceof NodeAggregateWasRemoved)
                && $domainEvent instanceof EmbedsContentStreamAndNodeAggregateIdentifier
            ) {
                $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier(
                    $domainEvent->getContentStreamIdentifier(),
                    $domainEvent->getNodeAggregateIdentifier()
                );

                if ($nodeAggregate) {
                    $this->scheduleCacheFlushJobForNodeAggregate(
                        $nodeAggregate->getContentStreamIdentifier(),
                        $nodeAggregate->getIdentifier()
                    );
                }
            }
        });

        return $graphProjector;
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

    public function shutdownObject(): void
    {
        $this->flushCache();
    }
}
