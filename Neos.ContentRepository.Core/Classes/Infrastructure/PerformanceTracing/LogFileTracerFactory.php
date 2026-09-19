<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\PerformanceTracing;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Exception\InvalidConfigurationException;
use Neos\ContentRepositoryRegistry\Factory\PerformanceTracer\PerformanceTracerFactoryInterface;

/**
 * @api
 */
final class LogFileTracerFactory implements PerformanceTracerFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryId, array $options): PerformanceTracerInterface
    {
        isset($options['fileName']) || throw InvalidConfigurationException::fromMessage('Content repository "%s" does not have performanceTracer.options.fileName configured. Recommended: %%FLOW_PATH_DATA%%Logs/ContentRepositoryProfile.log', $contentRepositoryId->value);

        return new LogFileTracer($options['fileName'], $options['minimumMarkDurationMs'] ?? 0);
    }
}
