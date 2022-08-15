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


use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\RaceTrackerCatchUpHook;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;

/**
 * Value object for a single trace entry, as stored in Redis.
 *
 * For full docs and context, see {@see RaceTrackerCatchUpHook}
 *
 * @internal
 */
final class TraceEntry
{
    /**
     * @var array<string,bool>
     */
    public array $pidsInCriticalSection;

    public function __construct(
        public readonly string $id,
        public readonly string $pid,
        public readonly TraceEntryType $type,
        public readonly array $payload,
    )
    {
    }

    public function printTableRow(array $pids): array
    {
        $cellOptions = [];
        if (count($this->pidsInCriticalSection) > 1) {
            $cellOptions['style'] = new TableCellStyle([
                // or
                'cellFormat' => '<error>%s</error>',
            ]);
        }

        $tableRow = [
            new TableCell($this->id, $cellOptions),
        ];

        foreach ($pids as $pid) {
            $tableCell = '';
            if (isset($this->pidsInCriticalSection[$pid])) {
                $tableCell = '* ';
            }

            if ($pid === $this->pid && $this->type === TraceEntryType::LockWillBeReleasedIfItWasAcquiredBefore) {
                $tableCell = '_';
            }

            if ($pid === $this->pid && !empty($this->payload)) {
                $tableCell .= json_encode($this->payload, JSON_PRETTY_PRINT);
            }
            $tableRow[] = new TableCell($tableCell, $cellOptions);
        }

        return $tableRow;
    }

    private function idLabel(): string
    {

        return $this->id;

    }
}
