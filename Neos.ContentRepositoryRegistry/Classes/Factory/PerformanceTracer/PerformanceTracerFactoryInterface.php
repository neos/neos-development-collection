<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\PerformanceTracer;

use Neos\ContentRepository\Core\Infrastructure\PerformanceTracing\PerformanceTracerInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

interface PerformanceTracerFactoryInterface
{
    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): PerformanceTracerInterface;
}
