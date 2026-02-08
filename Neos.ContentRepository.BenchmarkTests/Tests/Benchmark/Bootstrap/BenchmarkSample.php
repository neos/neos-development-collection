<?php

declare(strict_types=1);

final readonly class BenchmarkSample
{
    public function __construct(
        /** Depth of the created graph, for estimating recursive CTEs */
        public int $depth,
        /** Command runtime in milliseconds */
        public int $commandRuntime,
        /** ID query time in microseconds */
        public int $idQueryTime,
        /** Child query time in microseconds */
        public int $childrenQueryTime,
        /** Parent query time in microseconds */
        public int $parentQueryTime,
        /** Descendants query time in microseconds */
        public int $descendantsQueryTime,
        /** Ancestors query time in microseconds */
        public int $ancestorsQueryTime,
    ) {
    }
}
