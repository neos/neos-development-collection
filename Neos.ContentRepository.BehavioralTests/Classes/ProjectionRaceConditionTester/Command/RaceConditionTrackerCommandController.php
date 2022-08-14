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

use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto\TraceEntry;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\RedisInterleavingLogger;
use Neos\Flow\Cli\CommandController;
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

    public function analyzeTraceCommand()
    {
        // TODO: DETECT DOUBLE PROCESSING OF EVENTS!!! (should also not happen :D)

        RedisInterleavingLogger::connect($this->configuration['redis']['host'], $this->configuration['redis']['port']);
        $traces = RedisInterleavingLogger::getTraces();

        $this->outputLine('');
        $this->outputLine('');
        $this->outputLine('<info>SUMMARY</info>');
        $this->outputLine('');
        $this->outputLine('The trace contains %d lines.', [count($traces)]);

        // Find Violations
        $violationIndices = $traces->findViolations();
        if (count($violationIndices)) {
            $this->outputLine('The trace contains <error>%d violations</error> at line(s) %s.', [count($violationIndices), implode(',', $violationIndices)]);
        } else {
            $this->outputLine('The trace contains <info>no violations</info>');
        }


        // PRINTER for every violationIndex (skip duplicates)
        $alreadyPrintedIndices = [];
        foreach ($violationIndices as $violationIndex) {
            if (isset($alreadyPrintedIndices[$violationIndex])) {
                // we have this error already displayed, so we do not need to render it again.
                continue;
            }

            // how much context should be displayed
            $startIndex = $violationIndex - 5;
            $endIndex = $violationIndex + 10;

            $pids = $traces->getPidSetInIndexRange($startIndex, $endIndex);
            $tableRows = [];
            $traces->iterateRange($startIndex, $endIndex, function (TraceEntry $traceEntry, int $i) use ($pids, &$tableRows, &$alreadyPrintedIndices) {
                $alreadyPrintedIndices[$i] = true;

                $tableRows[] = [$i, ...$traceEntry->printTableRow($pids)];
            });

            $this->outputLine('');
            $this->outputLine('');
            $this->outputLine('<info>DETAILS for violation at line ' . $violationIndex . '</info>');

            $table = new Table($this->output->getOutput());
            $table
                ->setHeaders(['Line', 'ID', ...array_map(fn(string $pid) => 'PID ' . $pid, $pids)])
                ->setRows($tableRows);
            $table->render();
        }

        $this->outputLine('');
        $this->outputLine('Legend: ');
        $this->outputLine('* the process is in the critical section (i.e. needed to acquire the lock before)');
        $this->outputLine('_ The lock will be released directly after.');
        $this->outputLine('');
        $this->outputLine('If more than one process is in the critical section at the same time, we have detected an invalid state (i.e. a bug somewhere in the synchronization logic).');

        $this->outputLine('');

        if (!empty($violationIndices)) {
            $this->sendAndExit(1);
        }
    }
}
