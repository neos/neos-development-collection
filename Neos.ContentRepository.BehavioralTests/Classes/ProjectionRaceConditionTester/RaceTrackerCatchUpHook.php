<?php

/*
 * This file is part of the Neos.ContentRepository.BehavioralTests package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester;

use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto\TraceEntries;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto\TraceEntryType;
use Neos\EventStore\Model\EventEnvelope;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\SubprocessProjectionCatchUpTrigger;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;

/**
 * We had some race conditions in projections, where {@see DoctrineCheckpointStorage} was not working properly.
 * We saw some non-deterministic, random errors when running the tests - unluckily only on Linux, not on OSX:
 * On OSX, forking a new subprocess in {@see SubprocessProjectionCatchUpTrigger} is *WAY* slower than in Linux;
 * and thus the race conditions which appears if two projector instances of the same class run concurrently
 * won't happen (or are way less likely).
 *
 *
 * ## Our Goal: Detect if/when a Projection runs twice at the same time.
 *
 * The system must ENSURE that a given projection NEVER runs concurrently; so this is the case we need to detect.
 *
 * This means, the following is the behavior we want to have:
 *
 * ```
 * Process A         acquireLock(  |[  ) processEvent() releaseLock(  ]  )
 * Process B             acquireLock(  |                                [  ) processEvent() releaseLock(  ]  )
 * ```
 *
 * (i.e. Process B will wait inside acquireLock() until the lock is released (i.e. Process A finished), and then
 * continue.
 *
 *
 * A WRONG and UNDEFINED behavior looks as follows:
 *
 * ```
 * Process A         acquireLock(  |[  )  applyEvent()  releaseLock(  ]  )
 * Process B             acquireLock(  | [ )  applyEvent()  releaseLock(  ]  )
 *                                        ^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 *                                        During this time period, two
 *                                        processes run the projection
 *                                        concurrently.
 * ```
 *
 *
 * **Legend for the flow diagrams above**
 *
 * ```
 * --> time   |            [                             ]
 *            ^            ^                             ^
 *    try to acquire     lock acquired                  lock released
 *    the lock
 * ```
 *
 *
 * ## Implementation Idea: Race Detector with Redis
 *
 * We implement a custom CatchUpHook (this class {@see RaceTrackerCatchUpHook}) which is notified during
 * the projection run.
 *
 * When {@see onBeforeEvent} is called, we know that we are inside applyEvent() in the diagram above,
 * thus we know the lock *HAS* been acquired.
 * When {@see onBeforeBatchCompleted}is called, we know the lock will be released directly afterwards.
 *
 * We track these timings across processes in a single Redis Stream. Because Redis is single-threaded,
 * we can be sure that we observe the correct, total order of interleavings *across multiple processes*
 * inside the single trace.
 *
 *
 * ## Race Detector Algorithm
 *
 * We sequentially go through the stream, we continuously track for which PIDs a transaction is currently
 * open.
 *
 * When a transaction is open for more than one PID, we know that we found a race.
 *
 * This algorithm is implemented in {@see TraceEntries::findProjectionConcurrencyViolations()}.
 *
 *
 * ## Duplicate Processing Algorithm
 *
 * At the same time, an Event should never be processed multiple times by the same Projector. We additionally
 * detect this by remembering the sequence numbers of seen events; and if we have already seen the sequence
 * number already, we know this is an error. This is implemented in {@see TraceEntries::findDoubleProcessingOfEvents()}.
 *
 * @internal
 */
final class RaceTrackerCatchUpHook implements CatchUpHookInterface
{
    /**
     * @Flow\InjectConfiguration("raceConditionTracker")
     * @var array<mixed>
     */
    protected $configuration;
    private bool $inCriticalSection = false;

    public function onBeforeCatchUp(): void
    {
        RedisInterleavingLogger::connect($this->configuration['redis']['host'], $this->configuration['redis']['port']);
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        $this->inCriticalSection = true;
        RedisInterleavingLogger::trace(TraceEntryType::InCriticalSection, [
            'evt' => $eventEnvelope->event->type->value,
            'seq' => $eventEnvelope->sequenceNumber->value,
            'id' => $eventEnvelope->event->id->value
        ]);
    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
    }

    public function onBeforeBatchCompleted(): void
    {
        // we only want to track relevant lock release calls (i.e. if we were in the event processing loop before)
        if ($this->inCriticalSection) {
            $this->inCriticalSection = false;
            RedisInterleavingLogger::trace(TraceEntryType::LockWillBeReleasedIfItWasAcquiredBefore);
        }
    }

    public function onAfterCatchUp(): void
    {
    }
}
