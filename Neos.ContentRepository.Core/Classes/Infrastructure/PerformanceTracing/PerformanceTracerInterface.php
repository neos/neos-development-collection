<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\PerformanceTracing;

/**
 * Interface for tracing performance and execution flow in the Content Repository.
 *
 * @api (experimental) for external tracers. They might need to be adjusted in further versions.
 */
interface PerformanceTracerInterface
{
    /**
     * Creates a named span that traces the execution until the corresponding {@see self::closeSpan()}
     * is called. {@see self::openSpan()} and {@see self::closeSpan()} always need to be called
     * in pairs.
     *
     * A span represents a unit of work with a defined beginning and end. Any mark()
     * calls made during the closure execution are automatically associated with this span.
     * Spans can be nested, allowing for hierarchical performance analysis.
     *
     * Suggested pattern:
     *
     *      $this->performanceTracer?->openSpan('foo');
     *      try {
     *          // doFoo -> your code here
     *          $this->performanceTracer?->mark('doFoo')
     *          // doBar -> your code here
     *          $this->performanceTracer?->mark('doBar')
     *      } finally {
     *          $this->performanceTracer?->closeSpan();
     *      }
     *
     * @param string $name A descriptive name for this span (e.g., "contentRepository::handle")
     * @param array<string, mixed> $params attributes to attach to the span (e.g., ['c' => 'CreateNode'])
     */
    public function openSpan(string $name, array $params = []): void;

    /**
     * Close a span, opened by {@see self::openSpan()} before.
     * @return void
     */
    public function closeSpan(): void;

    /**
     * Creates a point-in-time marker AFTER an operation completes, within the current span.
     *
     * Markers measure the elapsed time from the previous mark (or span start) up to and
     * including the operation just completed. Place mark() calls immediately AFTER the
     * operation you want to measure.
     *
     * @param string $name A descriptive name identifying the operation that just completed
     * @param array<string, mixed> $params attributes to attach to the span (e.g., ['c' => 'CreateNode'])
     */
    public function mark(string $name, array $params = []): void;
}
