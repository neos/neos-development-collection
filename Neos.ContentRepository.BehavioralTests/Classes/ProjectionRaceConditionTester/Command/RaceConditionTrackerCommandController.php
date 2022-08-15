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

namespace Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Command;

use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto\TraceEntries;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto\TraceEntry;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\RedisInterleavingLogger;
use Neos\Flow\Cli\CommandController;
use Neos\Utility\Files;
use Symfony\Component\Console\Helper\Table;

/**
 * For full docs and context, see {@see RaceTrackerCatchUpHook}
 *
 * @internal
 */
final class RaceConditionTrackerCommandController extends CommandController
{
    /**
     * @Flow\InjectConfiguration("raceConditionTracker")
     * @var array
     */
    protected $configuration;

    public function resetCommand()
    {
        RedisInterleavingLogger::connect($this->configuration['redis']['host'], $this->configuration['redis']['port']);
        RedisInterleavingLogger::reset();
    }

    public function analyzeTraceCommand(string $storeTrace = null)
    {
        RedisInterleavingLogger::connect($this->configuration['redis']['host'], $this->configuration['redis']['port']);
        $traces = RedisInterleavingLogger::getTraces();

        $this->outputLine('');
        $this->outputLine('');
        $this->outputLine('<info>SUMMARY</info>');
        $this->outputLine('');
        $this->outputLine('The trace contains %d lines.', [count($traces)]);

        // Find Violations
        $projectionConcurrencyViolationIndices = $traces->findProjectionConcurrencyViolations();
        if (count($projectionConcurrencyViolationIndices)) {
            $this->outputLine('The trace contains <error>%d violations of projection no-concurrency invariant</error> at line(s) %s.', [count($projectionConcurrencyViolationIndices), implode(',', $projectionConcurrencyViolationIndices)]);
        } else {
            $this->outputLine('The trace contains <info>no violations of projection no-concurrency invariant</info>.');
        }

        $doubleProcessedEventIndices = $traces->findDoubleProcessingOfEvents();
        if (count($doubleProcessedEventIndices)) {
            $this->outputLine('The trace contains <error>%d double-processed event pairs (DBL)</error>.', [count($doubleProcessedEventIndices)]);
        } else {
            $this->outputLine('The trace contains <info>no double-processed event pairs</info>.');
        }

        $this->printViolations($traces, $projectionConcurrencyViolationIndices, $doubleProcessedEventIndices);

        $this->outputLine('');
        $this->outputLine('Legend: ');
        $this->outputLine('*    The process is in the critical section (i.e. needed to acquire the lock before).');
        $this->outputLine('_    The lock will be released directly after.');
        $this->outputLine('DBL  The event was processed multiple times.');
        $this->outputLine('');
        $this->outputLine('If more than one process is in the critical section at the same time, we have detected an invalid state (i.e. a bug somewhere in the synchronization logic).');

        $this->outputLine('');

        if (!empty($storeTrace)) {
            Files::createDirectoryRecursively(dirname($storeTrace));
            file_put_contents($storeTrace, $traces->asNdJson());
            $this->outputLine('The full trace file was written to <info>%s</info>.', [$storeTrace]);
            $this->outputLine('');
        }


        if (!empty($projectionConcurrencyViolationIndices)) {
            $this->sendAndExit(1);
        }
        if (!empty($doubleProcessedEventIndices)) {
            $this->sendAndExit(1);
        }
    }

    private function printViolations(TraceEntries $traces, array $projectionConcurrencyViolationIndices, array $doubleProcessedEventIndices)
    {
        $alreadyPrintedIndices = [];

        // we want to display both kinds of violation
        $projectionConcurrencyViolationIndices = [
            ...$projectionConcurrencyViolationIndices,
            ...array_keys($doubleProcessedEventIndices)
        ];
        foreach ($projectionConcurrencyViolationIndices as $violationIndex) {
            if (isset($alreadyPrintedIndices[$violationIndex])) {
                // we have this error already displayed, so we do not need to render it again.
                continue;
            }

            // how much context should be displayed
            $startIndex = $violationIndex - 5;
            $endIndex = $violationIndex + 10;

            $pids = $traces->getPidSetInIndexRange($startIndex, $endIndex);
            $tableRows = [];
            $traces->iterateRange($startIndex, $endIndex, function (TraceEntry $traceEntry, int $i) use ($pids, &$tableRows, &$alreadyPrintedIndices, $doubleProcessedEventIndices) {
                $alreadyPrintedIndices[$i] = true;

                $firstRow = $i;
                if (isset($doubleProcessedEventIndices[$i])) {
                    $firstRow .= ' <error>DBL</error>';
                }
                $tableRows[] = [$firstRow, ...$traceEntry->printTableRow($pids)];
            });

            $this->outputLine('');
            $this->outputLine('');
            $this->outputLine('<info>DETAILS for projection invariant violation around line ' . $violationIndex . '</info>');

            $table = new Table($this->output->getOutput());
            $table
                ->setHeaders(['Line', 'ID', ...array_map(fn(string $pid) => 'PID ' . $pid, $pids)])
                ->setRows($tableRows);
            $table->render();
        }
    }
}
