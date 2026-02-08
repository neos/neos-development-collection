<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BenchmarkTests\Tests\Benchmark\Bootstrap;

final readonly class BenchmarkSample
{
    public function __construct(
        /** Runtime in milliseconds */
        public int $runtime,
    ) {
    }
}
