<?php
namespace Neos\ContentRepositoryRegistry\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpLockIdentifier;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\PersistingStoreInterface;

/**
 * This encapsulates logic to prevent _some_ duplicate catch requests
 * across multiple processes. It utilizes a shared lock to ignore more than
 * a single catchUp request which will be executed as soon as possible,
 * which means when there is no lock on the catchUp itself. The catchUp locks
 * it's "running" state internally to prevent concurrency issues on the projections,
 * in here we just check if that lock was already acquired (and thus a catchUp is running).
 */
final class CatchUpDeduplicationQueue
{
    private LockFactory $lockFactory;

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly PersistingStoreInterface $lockStorage,
        private readonly ProjectionCatchUpTriggerInterface $projectionCatchUpTrigger
    ) {
        $this->lockFactory = new LockFactory($this->lockStorage);
    }

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

    private function triggerCatchUpAndReturnQueued(Projections $projections): Projections
    {
        $projectionsToCatchUp = [];
        $queuedProjections = [];
        foreach ($projections as $projection) {
            $runLock = $this->lockFactory->createLock(ProjectionCatchUpLockIdentifier::createRunning($this->contentRepositoryId, $projection::class)->value);
            if (!$runLock->isAcquired()) {
                // We are about to start a catchUp and can therefore discard any queue that exists right now, apparently someone else is waiting for it.
                $this->tryReleaseQueuedLock($projection::class);
                $projectionsToCatchUp[] = $projection;
                continue;
            }

            if ($this->tryAcquireQueuedLock($projection::class)) {
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
            $queuedLock = $this->queuedLock($projection::class);

            if (!$queuedLock->isAcquired()) {
                // was dequeued, we can drop it
                continue;
            }

            $runLock = $this->lockFactory->createLock(ProjectionCatchUpLockIdentifier::createRunning($this->contentRepositoryId, $projection::class)->value);

            if ($runLock->isAcquired() === false) {
                // We are about to start a catchUp and can therefore discard any queue that exists right now, apparently someone else is waiting for it.
                $this->tryReleaseQueuedLock($projection::class);
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
    private function tryAcquireQueuedLock(string $projectionClassName): bool
    {
        $queuedLock = $this->queuedLock($projectionClassName);
        try {
            return $queuedLock->acquire();
        } catch (LockConflictedException|LockAcquiringException $_) {
            return false;
        }
    }

    /**
     * @param class-string $projectionClassName
     * @return void
     */
    private function tryReleaseQueuedLock(string $projectionClassName): void
    {
        $queuedLock = $this->queuedLock($projectionClassName);
        try {
            $queuedLock->release();
        } catch (LockReleasingException $e) {
            // lock might already be released, this is fine
        }
    }

    /**
     * @param class-string $projectionClassName
     * @return LockInterface
     */
    private function queuedLock(string $projectionClassName): LockInterface
    {
        $lockIdentifierQueued = ProjectionCatchUpLockIdentifier::createQueued($this->contentRepositoryId, $projectionClassName)->value;
        $key = $this->createLockWithKeyState($lockIdentifierQueued, md5($lockIdentifierQueued));
        return new Lock($key, $this->lockStorage, 30.0);
    }

    private function createLockWithKeyState(string $lockResource, string $keyState): Key
    {
        $key = new Key($lockResource);
        $key->setState($this->lockStorage::class, $keyState);

        return $key;
    }
}
