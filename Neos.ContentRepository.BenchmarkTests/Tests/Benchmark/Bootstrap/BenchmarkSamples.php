<?php

declare(strict_types=1);

final class BenchmarkSamples
{
    /**
     * @var array<string,BenchmarkSample>
     */
    private static array $samples;

    public static function reset(): void
    {
        self::$samples = [];
    }

    public static function addSample(string $sampleName, BenchmarkSample $sample): void
    {
        self::$samples[$sampleName] = $sample;
    }

    public static function getSample(string $sampleName): ?BenchmarkSample
    {
        return self::$samples[$sampleName] ?? null;
    }

    /**
     * @return array<string,BenchmarkSample>
     */
    public static function getSamples(): array
    {
        return self::$samples;
    }
}
