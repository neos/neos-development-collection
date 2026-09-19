<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\PerformanceTracing;

/**
 * A {@see PerformanceTracerInterface} specialised for measuring replay/catchup throughput: it counts the distinct
 * events applied while the {@see SubscriptionEngine} catches up and renders the result as a standalone SVG line
 * chart. Intended as an ad-hoc before/after measurement tool; the typical use is `subscription:replay` with this
 * tracer configured.
 *
 * It derives the event count from the {@see TracePoint::ProjectionApply} mark (and its `sequenceNumber` param)
 *
 * Optionally a list of {@see ProbeInterface}s (e.g. table row counts via SQL) is sampled regularily.
 *
 * Each probe is drawn as its own chart underneath the throughput chart (own Y axis, shared time X axis).
 *
 * The SVG is (re)written incrementally on each sample, so a chart can be viewed during the run already..
 *
 * @internal a debugging/measurement aid; build it via a {@see PerformanceTracerFactoryInterface}
 */
final class ReplayThroughputTracer implements PerformanceTracerInterface
{
    private const NANOS_PER_SECOND = 1_000_000_000;

    // SVG geometry (all values are user units = px in the default viewBox)
    private const WIDTH = 1000;
    private const MARGIN_LEFT = 70;   // room for the Y axis labels
    private const MARGIN_RIGHT = 20;
    private const PANEL_HEIGHT = 240; // plot height of a single chart
    private const PANEL_GAP = 70;     // vertical space between stacked charts (also holds a chart's legend)
    private const TOP_MARGIN = 40;    // room for the title above the first chart

    private readonly int $startedAtNanos;

    private int $openSpanDepth = 0;

    private int $processedEvents = 0;

    /**
     * Sequence number of the event counted last, to deduplicate the per-subscriber {@see TracePoint::ProjectionApply}
     * marks: one event applied to N subscriptions fires N consecutive marks with the same sequence number.
     */
    private ?int $lastSequenceNumber = null;

    private int $lastSampleNanos;

    /**
     * One entry per sampling tick. `panels` maps a probe's chart title to that probe's labelled values at the tick.
     *
     * @var list<array{elapsedSeconds: float, events: int, panels: array<string, array<string, float>>}>
     */
    private array $samples = [];

    /**
     * @param string $svgPath the file the SVG chart is (incrementally) written to
     * @param list<ProbeInterface> $probes sampled on each (throttled) heartbeat; one chart per probe
     * @param float $sampleIntervalSeconds minimum wall-time between samples (and incremental SVG writes)
     */
    public function __construct(
        private readonly string $svgPath,
        private readonly array $probes = [],
        private readonly float $sampleIntervalSeconds = 2.0,
    ) {
        $this->startedAtNanos = hrtime(true);
        $this->lastSampleNanos = $this->startedAtNanos;
    }

    public function openSpan(string|TracePoint $name, array $params = []): void
    {
        $this->openSpanDepth++;
    }

    public function mark(string|TracePoint $name, array $params = []): void
    {
        if (TracePoint::ProjectionApply->equals($name)) {
            // Count events, not projection applies: the same event is applied to every subscription and thus fires
            // one mark per subscription. Those marks are consecutive and carry the same sequence number, so we only
            // count when the sequence number changes.
            $sequenceNumber = $params['sequenceNumber'] ?? null;
            if ($sequenceNumber !== $this->lastSequenceNumber) {
                $this->processedEvents++;
                $this->lastSequenceNumber = $sequenceNumber;

                // we need to sample every $this->sampleIntervalSeconds
                if ((hrtime(true) - $this->lastSampleNanos) / self::NANOS_PER_SECOND >= $this->sampleIntervalSeconds) {
                    $this->takeSample();
                }
            }
        }
    }

    public function closeSpan(): void
    {
        $this->openSpanDepth = max(0, $this->openSpanDepth - 1);
        if ($this->openSpanDepth === 0) {
            // a full top-level operation finished – take a final sample and write the chart
            $this->takeSample();
        }
    }

    private function takeSample(): void
    {
        // Only record/write once we are actually replaying events. This keeps the tracer dormant during ordinary
        // command handling (which still opens/closes spans) and avoids writing an empty chart.
        if ($this->processedEvents === 0) {
            return;
        }
        $panels = [];
        foreach ($this->probes as $probe) {
            try {
                $panels[$probe->label()] = $probe->sample();
            } catch (\Throwable) {
                // ignore – measurement must never interfere with the traced process
            }
        }
        $this->samples[] = [
            'elapsedSeconds' => (hrtime(true) - $this->startedAtNanos) / self::NANOS_PER_SECOND,
            'events' => $this->processedEvents,
            'panels' => $panels,
        ];
        $this->lastSampleNanos = hrtime(true);
        $this->persistSvg();
    }

    /**
     * Atomically writes the current chart (temp file + rename) so an interrupt mid-write cannot leave a corrupt
     * file behind. Write failures are intentionally ignored – measurement must never break the traced process.
     */
    private function persistSvg(): void
    {
        $tmpPath = $this->svgPath . '.tmp';
        if (@file_put_contents($tmpPath, $this->renderSvg()) !== false) {
            @rename($tmpPath, $this->svgPath);
        }
    }

    /**
     * Builds the whole SVG document.
     *
     * Layout: a bold title line, then a vertical stack of charts. Chart 0 is always the cumulative throughput
     * (events over time); each configured probe adds one more chart below it. Every chart shares the same time
     * (X) axis but has its own value (Y) axis, so series of very different magnitudes never get flattened into
     * one scale.
     *
     * The actual drawing of a single chart lives in {@see renderPanel()}; this method only decides which charts
     * exist, their value range and vertical position, and the overall canvas height.
     */
    private function renderSvg(): string
    {
        // Map a number of seconds to an X coordinate. The time axis spans 0 .. the last sample's elapsed time.
        $plotWidth = self::WIDTH - self::MARGIN_LEFT - self::MARGIN_RIGHT;
        $totalSeconds = max($this->samples === [] ? 0.0 : end($this->samples)['elapsedSeconds'], 0.000_000_001);
        $x = static fn (float $seconds): float => self::MARGIN_LEFT + ($seconds / $totalSeconds) * $plotWidth;

        // Chart 0 – cumulative throughput. A single line "events" starting at the origin; its Y axis is pinned to
        // the total number of processed events so the curve fills the chart.
        $eventSeries = ['events' => [[0.0, 0.0]]];
        foreach ($this->samples as $sample) {
            $eventSeries['events'][] = [$sample['elapsedSeconds'], (float)$sample['events']];
        }
        $panels = [[
            'axisLabel' => 'events processed (cumulative)',
            'series' => $eventSeries,
            'max' => (float)max($this->processedEvents, 1),
            'legend' => false,
        ]];

        // One chart per probe. All lines of a probe share that chart's Y axis, scaled to the probe's largest value
        // seen so far.
        foreach ($this->probePanelTitles() as $title) {
            $series = $this->collectPanelSeries($title);
            $max = 1.0;
            foreach ($series as $points) {
                foreach ($points as [, $value]) {
                    $max = max($max, $value);
                }
            }
            $panels[] = ['axisLabel' => $title, 'series' => $series, 'max' => $max, 'legend' => true];
        }

        // Canvas height grows with the number of stacked charts (+ a trailing margin for the last X axis labels).
        $count = count($panels);
        $height = self::TOP_MARGIN + $count * self::PANEL_HEIGHT + max(0, $count - 1) * self::PANEL_GAP + 40;

        $avgThroughput = $this->processedEvents / $totalSeconds;
        $title = sprintf(
            '%s events in %ss — avg %s events/s',
            number_format($this->processedEvents, 0),
            number_format($totalSeconds, 1),
            number_format($avgThroughput, 0)
        );
        $body = sprintf('<text x="%d" y="24" font-size="15" font-weight="bold" fill="#222">%s</text>', self::MARGIN_LEFT, $title);

        foreach ($panels as $index => $panel) {
            $top = self::TOP_MARGIN + $index * (self::PANEL_HEIGHT + self::PANEL_GAP);
            $body .= self::renderPanel($panel['axisLabel'], $panel['series'], $panel['max'], $top, $top + self::PANEL_HEIGHT, $x, $totalSeconds, $panel['legend']);
        }

        $width = self::WIDTH;
        return <<<SVG
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}" font-family="sans-serif">
              {$body}
            </svg>
            SVG;
    }

    /**
     * Ordered, de-duplicated list of probe chart titles (one chart per probe, in configuration order).
     *
     * @return list<string>
     */
    private function probePanelTitles(): array
    {
        $titles = [];
        foreach ($this->probes as $probe) {
            $titles[$probe->label()] = true;
        }
        return array_keys($titles);
    }

    /**
     * Reshapes the per-tick samples of one chart into one time series per line label. A line only has points for
     * the ticks where the probe actually returned that label, so lines that appear/disappear over time are fine.
     *
     * @return array<string, list<array{0: float, 1: float}>> lineLabel => list of [elapsedSeconds, value]
     */
    private function collectPanelSeries(string $title): array
    {
        $series = [];
        foreach ($this->samples as $sample) {
            foreach ($sample['panels'][$title] ?? [] as $lineLabel => $value) {
                $series[$lineLabel][] = [$sample['elapsedSeconds'], $value];
            }
        }
        return $series;
    }

    /**
     * Renders one chart between $top and $bottom: horizontal gridlines + Y labels at "nice" round values, the
     * shared X axis, a rotated axis caption, one coloured polyline per series, and – if requested – a legend in
     * the gap above the chart. All series share this chart's Y axis.
     *
     * @param array<string, list<array{0: float, 1: float}>> $series lineLabel => list of [elapsedSeconds, value]
     */
    private static function renderPanel(string $axisLabel, array $series, float $maxValue, float $top, float $bottom, callable $x, float $totalSeconds, bool $showLegend): string
    {
        // d3-style "nice" Y axis: pick a round step (1/2/5 × 10ⁿ) and round the top of the range up to a multiple
        // of it, so labels read 0, 5,000, 10,000 … instead of 0, 8,333, 16,667 …
        $step = self::niceStep($maxValue / 5);
        $niceMax = max($step, ceil($maxValue / $step) * $step);
        $decimals = $step < 1.0 ? (int)ceil(-log10($step)) : 0;

        // Map a value to a Y coordinate within this chart (0 at the bottom edge, $niceMax at the top edge).
        $y = static fn (float $value): float => $bottom - ($value / $niceMax) * ($bottom - $top);

        // Y axis: a gridline/label at every nice step from 0 up to (and including) the nice maximum.
        $out = '';
        for ($value = 0.0; $value <= $niceMax + $step * 0.001; $value += $step) {
            $out .= self::yTick(self::MARGIN_LEFT, self::WIDTH - self::MARGIN_RIGHT, $y($value), number_format($value, $decimals));
        }
        // X axis (time) + a rotated caption naming the chart.
        $out .= self::xAxis($x, $totalSeconds, $top, $bottom);
        $out .= sprintf('<text x="18" y="%.1f" font-size="11" fill="#555" transform="rotate(-90 18 %.1f)" text-anchor="end">%s</text>', $top + 60, $top + 60, htmlspecialchars($axisLabel, ENT_QUOTES));

        // One polyline per series; colours cycle through the palette. Optional legend sits in the gap above.
        $palette = ['#1f77b4', '#2ca02c', '#d62728', '#9467bd', '#ff7f0e', '#8c564b'];
        $legendY = $top - 22;
        $index = 0;
        foreach ($series as $label => $points) {
            $color = $palette[$index % count($palette)];
            $linePoints = [];
            foreach ($points as [$seconds, $value]) {
                $linePoints[] = sprintf('%.1f,%.1f', $x($seconds), $y($value));
            }
            $out .= sprintf('<polyline fill="none" stroke="%s" stroke-width="1.5" points="%s"/>', $color, implode(' ', $linePoints));
            if ($showLegend) {
                $legendX = self::MARGIN_LEFT + $index * 200;
                $out .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="2"/>', $legendX, $legendY, $legendX + 20, $legendY, $color);
                $out .= sprintf('<text x="%d" y="%d" font-size="11" fill="#555">%s</text>', $legendX + 26, $legendY + 4, htmlspecialchars((string)$label, ENT_QUOTES));
            }
            $index++;
        }
        return $out;
    }

    /**
     * A single horizontal gridline across the plot with a right-aligned value label to the left of the Y axis.
     */
    private static function yTick(float $x1, float $x2, float $y, string $label): string
    {
        return sprintf(
            '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#e0e0e0" stroke-width="1"/>' .
            '<text x="%.1f" y="%.1f" font-size="11" fill="#555" text-anchor="end">%s</text>',
            $x1,
            $y,
            $x2,
            $y,
            $x1 - 8,
            $y + 4,
            $label
        );
    }

    /**
     * X axis gridlines + labels for the chart between $top and $bottom. The tick spacing adapts to the total
     * duration (down to sub-second), so the chart stays readable whether a run lasts milliseconds or hours.
     */
    private static function xAxis(callable $x, float $totalSeconds, float $top, float $bottom): string
    {
        // Aim for ~6 ticks, rounded to a "nice" step; pick label precision so sub-second steps aren't all "0s".
        $step = self::niceStep($totalSeconds / 6);
        $decimals = $step < 1.0 ? (int)ceil(-log10($step)) : 0;
        $out = '';
        for ($seconds = 0.0; $seconds <= $totalSeconds + $step * 0.001; $seconds += $step) {
            $xx = $x($seconds);
            $out .= sprintf(
                '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#e0e0e0" stroke-width="1"/>' .
                '<text x="%.1f" y="%.1f" font-size="11" fill="#555" text-anchor="middle">%ss</text>',
                $xx,
                $top,
                $xx,
                $bottom,
                $xx,
                $bottom + 18,
                number_format($seconds, $decimals)
            );
        }
        return $out;
    }

    /**
     * Rounds a raw axis step up to the nearest "nice" value (1, 2 or 5 × a power of ten), scaling cleanly into
     * the sub-second range (…, 0.1, 0.2, 0.5, 1, 2, 5, …).
     */
    private static function niceStep(float $raw): float
    {
        if ($raw <= 0.0) {
            return 1.0;
        }
        $magnitude = 10 ** floor(log10($raw));
        $normalized = $raw / $magnitude;
        $nice = match (true) {
            $normalized <= 1.0 => 1.0,
            $normalized <= 2.0 => 2.0,
            $normalized <= 5.0 => 5.0,
            default => 10.0,
        };
        return $nice * $magnitude;
    }
}
