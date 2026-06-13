<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\PerformanceTracer;

use Doctrine\ORM\EntityManagerInterface;
use Neos\ContentRepository\Core\Infrastructure\PerformanceTracing\PerformanceTracerInterface;
use Neos\ContentRepository\Core\Infrastructure\PerformanceTracing\ReplayThroughputTracer;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Exception\InvalidConfigurationException;
use Neos\Flow\Annotations as Flow;

/**
 * Builds a {@see ReplayThroughputTracer} from settings. Each configured `sqlProbes` entry becomes a
 * {@see SqlProbe} over the default Flow DB connection.
 *
 * Example settings:
 *
 *   performanceTracer:
 *     factoryObjectName: Neos\ContentRepositoryRegistry\Factory\PerformanceTracer\ReplayThroughputTracerFactory
 *     options:
 *       fileName: '%FLOW_PATH_DATA%Logs/ContentRepositoryThroughput.svg'
 *       sampleIntervalSeconds: 2.0
 *       sqlProbes:
 *         'nodes': 'SELECT COUNT(*) FROM cr_default_p_graph_node'
 *         'hierarchy relations': 'SELECT COUNT(*) FROM cr_default_p_graph_hierarchyrelation'
 *
 * @api
 */
final class ReplayThroughputTracerFactory implements PerformanceTracerFactoryInterface
{
    #[Flow\Inject]
    protected EntityManagerInterface $entityManager;

    public function build(ContentRepositoryId $contentRepositoryId, array $options): PerformanceTracerInterface
    {
        isset($options['fileName']) || throw InvalidConfigurationException::fromMessage('Content repository "%s" does not have performanceTracer.options.fileName configured. Recommended: %%FLOW_PATH_DATA%%Logs/ContentRepositoryThroughput.svg', $contentRepositoryId->value);

        $connection = $this->entityManager->getConnection();
        $probes = [];
        foreach ($options['sqlProbes'] ?? [] as $label => $sql) {
            $probes[] = new SqlProbe((string)$label, $connection, (string)$sql);
        }

        return new ReplayThroughputTracer(
            $options['fileName'],
            $probes,
            (float)($options['sampleIntervalSeconds'] ?? 2.0),
        );
    }
}
