<?php

/** @noinspection PhpComposerExtensionStubsInspection */

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
 * @implements \ArrayAccess<int,TraceEntry>
 */
final class TraceEntries implements \ArrayAccess, \Countable
{
    /**
     * @param TraceEntry[] $traces
     */
    public function __construct(
        private readonly array $traces,
    ) {
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

    /**
     * @return array<string>
     */
    public function getPidSetInIndexRange(int $startIndex, int $endIndex): array
    {
        $pids = [];

        $this->iterateRange($startIndex, $endIndex, function (TraceEntry $traceEntry) use (&$pids) {
            $pids[$traceEntry->pid] = $traceEntry->pid;
        });

        asort($pids);
        return array_values($pids);
    }

    /**
     * @param int $startIndex (inclusive)
     * @param int $endIndex (inclusive)
     */
    public function iterateRange(int $startIndex, int $endIndex, \Closure $callback): void
    {
        if ($startIndex < 0) {
            $startIndex = 0;
        }
        for ($i = $startIndex; $i <= $endIndex && isset($this->traces[$i]); $i++) {
            $callback($this->traces[$i], $i);
        }
    }

    /**
     * @return array<int> Violation Indices
     */
    public function findProjectionConcurrencyViolations(): array
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

    /**
     * It shall never happen that the same event (i.e. with the same sequence number) is processed multiple times by
     * the same projector.
     *
     * @return array<int,bool>
     */
    public function findDoubleProcessingOfEvents(): array
    {
        $doubleProcessedEventIndices = [];
        $alreadySeenIds = [];
        foreach ($this->traces as $i => $entry) {
            assert($entry instanceof TraceEntry);
            if (isset($entry->payload['id'])) {
                if (isset($alreadySeenIds[$entry->payload['id']])) {
                    // ERROR CASE: we've already seen the same sequence number; so we create an error listing.
                    $doubleProcessedEventIndices[$alreadySeenIds[$entry->payload['id']]] = true;
                    $doubleProcessedEventIndices[$i] = true;
                } else {
                    // we record the seen ID
                    $alreadySeenIds[$entry->payload['id']] = $i;
                }
            }
        }

        return $doubleProcessedEventIndices;
    }

    public function asNdJson(): string
    {
        $lines = [];
        foreach ($this->traces as $i => $entry) {
            assert($entry instanceof TraceEntry);
            $lines[] = json_encode([
                'idx' => $i,
                'id' => $entry->id,
                'pid' => $entry->pid,
                'type' => $entry->type->value,
                'payload' => $entry->payload
            ]);
        }

        return implode("\n", $lines);
    }
}
