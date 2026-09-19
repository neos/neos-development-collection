<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\PerformanceTracing;

/**
 * Simple file-based tracer that writes human-readable trace logs incrementally.
 *
 * Format: [timestamp] indentation name (duration)
 * - Spans use > (start) and < (end) with duration
 * - Marks use • with time since last mark
 * - Indentation shows nesting depth
 * - fflush() after each write ensures crash-safety
 *
 * Example:
 * [123.456] > handleCommand {"cmd":"CreateNode"}
 * [123.457]   • validation (+1.2 ms)
 * [123.459] < handleCommand (3.0 ms)
 *
 * @internal
 */
final class LogFileTracer implements PerformanceTracerInterface
{
    private bool $headerWritten = false;
    /** @var resource|null */
    private $fileHandle = null;
    /**
     * @var array<array{s: float, n: string}>
     */
    private array $openSpans = [];
    private float $lastMarkTime = 0.0;

    public function __construct(private readonly string $logFilePath, private readonly float $minimumMarkDurationMs)
    {
    }

    public function openSpan(string $name, array $params = []): void
    {
        $this->ensureFileOpen();

        $startTime = microtime(true);
        $paramsStr = empty($params) ? '' : ' ' . json_encode($params);

        $this->writeLine(sprintf(
            "[%.6f] %s> %s%s",
            $startTime,
            str_repeat('  ', count($this->openSpans)),
            $name,
            $paramsStr
        ));

        $this->openSpans[] = ['s' => $startTime, 'n' => $name];
        $this->lastMarkTime = $startTime;
    }

    public function closeSpan(): void
    {
        $this->ensureFileOpen();

        if (empty($this->openSpans)) {
            return;
        }

        $s = array_pop($this->openSpans);
        $startTime = $s['s'];
        $name = $s['n'];

        $endTime = microtime(true);
        $this->lastMarkTime = $endTime;
        $duration = ($endTime - $startTime) * 1000; // ms

        $this->writeLine(sprintf(
            "[%.6f] %s< %s (%.3f ms)",
            $endTime,
            str_repeat('  ', count($this->openSpans)),
            $name,
            $duration
        ));
    }

    public function mark(string $name, array $params = []): void
    {
        $this->ensureFileOpen();

        $currentTime = microtime(true);
        $duration = $this->lastMarkTime > 0
            ? ($currentTime - $this->lastMarkTime) * 1000
            : 0;

        if ($duration > $this->minimumMarkDurationMs) {
            $this->writeLine(sprintf(
                "[%.6f] %s• %s (+%.1f ms)   %s",
                $currentTime,
                str_repeat('  ', count($this->openSpans)),
                $name,
                $duration,
                count($params) > 0 ? json_encode($params) : ''
            ));
        }

        $this->lastMarkTime = $currentTime;
    }

    private function ensureFileOpen(): void
    {
        if ($this->fileHandle === null) {
            $fileHandle = fopen($this->logFilePath, 'a');
            if (is_resource($fileHandle)) {
                $this->fileHandle = $fileHandle;
            }
            // NOTE: no error handling if there were problems opening the file; as we do not want the tracer to fail the normal processes
        }

        if (!$this->headerWritten) {
            $this->writeLine("\n\n=== Trace started at " . date('Y-m-d H:i:s') . " ===");
            $this->headerWritten = true;
        }
    }

    private function writeLine(string $line): void
    {
        if ($this->fileHandle !== null) {
            fwrite($this->fileHandle, $line . "\n");
            fflush($this->fileHandle);
        }
    }

    public function __destruct()
    {
        if ($this->fileHandle !== null) {
            fclose($this->fileHandle);
        }
    }
}
