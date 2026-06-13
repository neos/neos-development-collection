<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\PerformanceTracing;

/**
 * A named measurement that can be read repeatedly while a trace is running, e.g. table row counts via SQL,
 * the current memory usage, … Sampled by {@see ReplayThroughputTracer} at its (throttled) sampling interval
 * and drawn as its own chart over time.
 *
 * A probe produces one chart (titled {@see label()}); a single {@see sample()} may return several labelled
 * values, each of which becomes one line within that chart sharing its Y axis. Return one value to draw a
 * single line, or several (e.g. from a UNION/GROUP BY query) to group related lines into the same chart.
 *
 * Implementations decide what they measure; this keeps measurement-specific dependencies (a DBAL connection
 * for SQL probes, …) out of the Content Repository core. {@see sample()} must be cheap-ish and read-only –
 * it runs in-process while e.g. a replay/catchup is going on.
 *
 * @api (experimental) for custom measurements wired into {@see ReplayThroughputTracer}
 */
interface ProbeInterface
{
    /**
     * Human-readable title of the chart this probe draws.
     */
    public function label(): string;

    /**
     * Reads the current values: line label => value. One entry draws a single line; several entries draw several
     * lines in this probe's chart (shared Y axis). Called once per sampling tick; must not throw (the tracer
     * ignores failures, but a throwing probe still costs a caught exception per tick).
     *
     * @return array<string, float>
     */
    public function sample(): array;
}
