<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BenchmarkTests\Tests\Benchmark\Bootstrap;

use Behat\Step\Then;
use PHPUnit\Framework\Assert;

trait BenchmarkSampling
{
    /**
     * @var array<string,BenchmarkSample>
     */
    protected array $samples = [];

    #[Then('/^I expect linear runtime growth between samples "([^"]*)" and "([^"]*)" with expected factor (\d+)$/')]
    public function iExpectLinearRuntimeGrowth(string $sample1, string $sample2, int $factor): void
    {
        $actualNormalizedGrowth = ($this->samples[$sample2]->runtime / $this->samples[$sample1]->runtime) / $factor;
        Assert::assertLessThan(
            expected: 2,
            actual: $actualNormalizedGrowth,
            message: 'Runtime growth appears to be non-linear, normalized growth is ' . $actualNormalizedGrowth . '.'
        );
    }
}
