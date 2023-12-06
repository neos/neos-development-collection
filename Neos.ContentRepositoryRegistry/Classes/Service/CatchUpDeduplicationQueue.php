<?php
namespace Neos\ContentRepositoryRegistry\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Symfony\Component\Lock\LockFactory;

/**
 * This encapsulates logic to provide exactly once catchUps
 * across multiple processes, leaving the way catchUps are done
 * to the ProjectionCatchUpTriggerInterface.
 */
final readonly class CatchUpDeduplicationQueue
{
    public function __construct(
        private ContentRepositoryId $contentRepositoryId,
        private LockFactory $lockFactoy,
        private ProjectionCatchUpTriggerInterface $projectionCatchUpTrigger
    ) {}

    public function requestCatchUp(Projections $projections): void
    {
        $queuedProjections = $this->triggerCatchUpAndReturnQueued($projections);

        /*
         * Due to the random nature of the retry delay (see below)
         * we define an absolute time limit spend in this loop as
         * end condition in case we cannot resolve the queue before.
         * */
        $startTime = $currentTime = microtime(true);
        $attempts = 0;
        /** @phpstan-ignore-next-line */
        while ($queuedProjections->isEmpty() === false && ($currentTime - $startTime) < 20) {
            // incrementally slower retries with some randomness to allow for tie breaks if parallel processes are in this loop.
            usleep(random_int(100, 100 + $attempts) + ($attempts * $attempts * 10));
            $queuedProjections = $this->retryQueued($queuedProjections);
            $attempts++;
            $currentTime = microtime(true);
        }
    }

    /**
     * @param class-string $projectionClassName
     * @return void
     */
    public function releaseCatchUpLock(string $projectionClassName): void
    {
        $runningLock = $this->lockFactoy->createLock($this->cacheKeyRunning($projectionClassName));
        $runningLock->isAcquired() && $runningLock->release();
    }

    private function triggerCatchUpAndReturnQueued(Projections $projections): Projections
    {
        $projectionsToCatchUp = [];
        $queuedProjections = [];
        foreach ($projections as $projection) {
            $runningLock = $this->lockFactoy->createLock($this->cacheKeyRunning($projection::class));
            $queuedLock = $this->lockFactoy->createLock($this->cacheKeyQueued($projection::class));
            if ($runningLock->acquire()) {
                // We are about to start a catchUp and can therefore discard any queue that exists right now, apparently someone else is waiting for it.
                $queuedLock->release();
                $projectionsToCatchUp[] = $projection;
                continue;
            }

            if ($queuedLock->acquire()) {
                $queuedProjections[] = $projection;
            }
        }

        $this->projectionCatchUpTrigger->triggerCatchUp(Projections::fromArray($projectionsToCatchUp));

        return Projections::fromArray($queuedProjections);
    }

    private function retryQueued(Projections $queuedProjections): Projections
    {
        $passToCatchUp = [];
        $stillQueuedProjections = [];

        foreach ($queuedProjections as $projection) {
            $runningLock = $this->lockFactoy->createLock($this->cacheKeyRunning($projection::class));
            $queuedLock = $this->lockFactoy->createLock($this->cacheKeyQueued($projection::class));

            if (!$queuedLock->isAcquired()) {
                // was dequeued, we can drop it
                continue;
            }

            if ($runningLock->acquire()) {
                // We are about to start a catchUp and can therefore discard any queue that exists right now, apparently someone else is waiting for it.
                $queuedLock->release();
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
