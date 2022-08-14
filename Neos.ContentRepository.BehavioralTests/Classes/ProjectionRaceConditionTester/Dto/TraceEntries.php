<?php /** @noinspection PhpComposerExtensionStubsInspection */

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

namespace Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto;


/**
 * Value object for a list of {@see TraceEntry} objects, as stored in-order in the Redis stream
 *
 * For full docs and context, see {@see RaceTrackerCatchUpHook}
 *
 * @internal
 */
final class TraceEntries implements \ArrayAccess, \Countable
{
    public function __construct(
        private readonly array $traces,
    )
    {
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->traces[$offset]);
    }

    public function offsetGet(mixed $offset): TraceEntry
    {
        return $this->traces[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException("Not supported");
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException("Not supported");
    }

    public function getPidSetInIndexRange(int $startIndex, int $endIndex): array
    {
        $pids = [];

        $this->iterateRange($startIndex, $endIndex, function (TraceEntry $traceEntry) use (&$pids) {
            $pids[$traceEntry->pid] = $traceEntry->pid;
        });

        asort($pids);
        return array_values($pids);
    }

    public function iterateRange(int $startIndex, int $endIndex, \Closure $callback)
    {
        if ($startIndex < 0) {
            $startIndex = 0;
        }
        for ($i = $startIndex; $i <= $endIndex && isset($this->traces[$i]); $i++) {
            $callback($this->traces[$i], $i);
        }
    }

    /**
     * @return array Violation Indices
     */
    public function findViolations(): array
    {
        // this array contains which PIDs (Process IDs) are right now in the critical section.
        // the key is the PID; the value is simply "true"
        $pidsInCriticalSection = [];

        // where multiple PIDs are in critical section (should not happen, this is what we want to detect)
        $violationIndices = [];

        // FINDER for critical errors (fills $violationIndices)
        foreach ($this->traces as $i => $entry) {
            assert($entry instanceof TraceEntry);
            if ($entry->type === TraceEntryType::InCriticalSection) {
                $pidsInCriticalSection[$entry->pid] = true;
            } elseif ($entry->type === TraceEntryType::LockWillBeReleasedIfItWasAcquiredBefore) {
                unset($pidsInCriticalSection[$entry->pid]);
            }

            $entry->pidsInCriticalSection = $pidsInCriticalSection;

            if (count($pidsInCriticalSection) > 1) {
                $violationIndices[] = $i;
            }
        }

        return $violationIndices;
    }

    public function count(): int
    {
        return count($this->traces);
    }
}
