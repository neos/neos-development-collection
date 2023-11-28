<?php
namespace Neos\ContentRepositoryRegistry\Service;

use Neos\Cache\Frontend\FrontendInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\Projections;

/**
 *
 */
final readonly class CatchUpDeduplicationQueue
{
    public function __construct(
        private ContentRepositoryId $contentRepositoryId,
        private FrontendInterface $catchUpLock,
        private ProjectionCatchUpTriggerInterface $projectionCatchUpTrigger
    ) {}

    public function requestCatchUp(Projections $projections): void
    {
        $queuedProjections = $this->triggerCatchUpAndReturnQueued($projections);
        $attempts = 0;
        /** @phpstan-ignore-next-line */
        while ($queuedProjections->isEmpty() === false) {
            usleep(random_int(100, 100 + $attempts) + ($attempts * $attempts * 10));
            $queuedProjections = $this->retryQueued($queuedProjections);
            $attempts++;
        }
    }

    /**
     * @param class-string $projectionClassName
     * @return void
     */
    public function releaseCatchUpLock(string $projectionClassName): void
    {
        $this->setStopped($projectionClassName);
    }

    private function triggerCatchUpAndReturnQueued(Projections $projections): Projections
    {
        $passToCatchUp = [];
        $queuedProjections = [];
        foreach ($projections as $projection) {
            if (!$this->isRunning($projection::class)) {
                $this->run($projection::class);
                // We are about to start a catchUp and can therefore discard any queue that exists right now, apparently someone else is waiting for it.
                $this->dequeue($projection::class);
                $passToCatchUp[] = $projection;
                continue;
            }

            if (!$this->isQueued($projection::class)) {
                $this->queue($projection::class);
                $queuedProjections[] = $projection;
            }
        }

        $this->projectionCatchUpTrigger->triggerCatchUp(Projections::fromArray($passToCatchUp));

        return Projections::fromArray($queuedProjections);
    }

    private function retryQueued(Projections $queuedProjections): Projections
    {
        $passToCatchUp = [];
        $stillQueuedProjections = [];
        foreach ($queuedProjections as $projection) {
            if (!$this->isQueued($projection::class)) {
                // was dequeued, we can drop it
                continue;
            }

            if (!$this->isRunning($projection::class)) {
                $this->run($projection::class);
                // We are about to start a catchUp and can therefore discard any queue that exists right now, apparently someone else is waiting for it.
                $this->dequeue($projection::class);
                $passToCatchUp[] = $projection;
                continue;
            }

            $stillQueuedProjections[] = $projection;
        }

        $this->projectionCatchUpTrigger->triggerCatchUp(Projections::fromArray($passToCatchUp));

        return Projections::fromArray($stillQueuedProjections);
    }

    /**
     * @param class-string $projectionClassName
     * @return bool
     */
    private function isRunning(string $projectionClassName): bool
    {
        return $this->catchUpLock->has($this->cacheKeyRunning($projectionClassName));
    }

    /**
     * @param class-string $projectionClassName
     * @return void
     */
    private function run(string $projectionClassName): void
    {
        $this->catchUpLock->set($this->cacheKeyRunning($projectionClassName), 1);
    }

    /**
     * @param class-string $projectionClassName
     * @return void
     */
    private function setStopped(string $projectionClassName): void
    {
        $this->catchUpLock->remove($this->cacheKeyRunning($projectionClassName));
    }

    /**
     * @param class-string $projectionClassName
     * @return bool
     */
    private function isQueued(string $projectionClassName): bool
    {
        return $this->catchUpLock->has($this->cacheKeyQueued($projectionClassName));
    }

    /**
     * @param class-string $projectionClassName
     * @return void
     */
    private function queue(string $projectionClassName): void
    {
        $this->catchUpLock->set($this->cacheKeyQueued($projectionClassName), 1);
    }

    /**
     * @param class-string $projectionClassName
     * @return void
     */
    private function dequeue(string $projectionClassName): void
    {
        $this->catchUpLock->remove($this->cacheKeyQueued($projectionClassName));
    }

    /**
     * @param class-string $projectionClassName
     * @return string
     */
    private function cacheKeyPrefix(string $projectionClassName): string
    {
        return $this->contentRepositoryId->value . '_' . md5($projectionClassName);
    }

    /**
     * @param class-string $projectionClassName
     * @return string
     */
    private function cacheKeyRunning(string $projectionClassName): string
    {
        return $this->cacheKeyPrefix($projectionClassName) . '_RUNNING';
    }

    /**
     * @param class-string $projectionClassName
     * @return string
     */
    private function cacheKeyQueued(string $projectionClassName): string
    {
        return $this->cacheKeyPrefix($projectionClassName) . '_QUEUED';
    }
}
