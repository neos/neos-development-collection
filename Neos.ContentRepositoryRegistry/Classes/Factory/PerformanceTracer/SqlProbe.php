<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\PerformanceTracer;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Infrastructure\PerformanceTracing\ProbeInterface;

/**
 * A {@see ProbeInterface} backed by an (arbitrary, read-only) SQL query, drawn as one chart. Can be used f.e.
 * to correlate replay throughput with table growth.
 *
 * The query result shape decides how many lines the chart has:
 * - one column  → a single line named after the chart, e.g. `SELECT COUNT(*) FROM cr_default_p_graph_node`
 * - two columns → one line per row, first column = line label, second = value. Group several measurements into
 *   one chart (shared Y axis) via UNION or GROUP BY, e.g.
 *       SELECT 'nodes' AS label, COUNT(*) AS value FROM cr_default_p_graph_node
 *       UNION ALL SELECT 'hierarchy relations', COUNT(*) FROM cr_default_p_graph_hierarchyrelation
 *
 * It runs on the same connection while a replay/catchup is in progress, so keep it cheap (it is sampled once
 * per sampling interval by {@see ReplayThroughputTracer} – every 2 seconds by default).
 *
 * @internal
 */
final class SqlProbe implements ProbeInterface
{
    public function __construct(
        private readonly string $label,
        private readonly Connection $connection,
        private readonly string $sql,
    ) {
    }

    public function label(): string
    {
        return $this->label;
    }

    public function sample(): array
    {
        $values = [];
        foreach ($this->connection->fetchAllNumeric($this->sql) as $row) {
            if (count($row) >= 2) {
                // (label, value) rows → one line per row
                $values[(string)$row[0]] = (float)$row[1];
            } else {
                // single scalar → one line named after the chart
                $values[$this->label] = (float)$row[0];
            }
        }
        return $values;
    }
}
