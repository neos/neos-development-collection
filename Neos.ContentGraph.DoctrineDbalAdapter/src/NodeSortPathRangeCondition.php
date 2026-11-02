<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeSortPath;
use Neos\ContentRepository\Dbal\Query\Parameter;
use Neos\ContentRepository\Dbal\Query\Parameters;
use Neos\ContentRepository\Dbal\Query\SqlWhereConditionInterface;

/**
 * @internal
 */
final readonly class NodeSortPathRangeCondition implements SqlWhereConditionInterface
{
    private function __construct(
        private NodeSortPath $nodeSortPath,
        private bool $includeStartingPoint,
        private ?int $maxDepth,
    ) {
    }

    /**
     * If startingPoint is included, we start the scan range with the current path to include it. Otherwise, we use
     * rangeStart() to exclude it.
     * E.g. Path is a0/a0: included -> path >= a0/a0; excluded -> path >= a0/a0/
     */
    public static function forNodeSortPath(
        NodeSortPath $nodeSortPath,
        bool $includeStartingPoint = false,
        ?int $maxDepth = null
    ): self {
        return new self(
            $nodeSortPath,
            $includeStartingPoint,
            $maxDepth
        );
    }

    public function getParameters(): Parameters
    {
        $parameters = [
            Parameter::string('rangeStart', $this->includeStartingPoint ? $this->nodeSortPath->value : $this->nodeSortPath->rangeStart()),
            Parameter::string('rangeEnd', $this->nodeSortPath->rangeEnd()),
        ];
        if ($this->maxDepth !== null) {
            $parameters[] = Parameter::integer('maxDepth', $this->maxDepth);
        }
        return Parameters::create(...$parameters);
    }

    public function toWhereSql(string $alias): string
    {
        $prefix = $alias !== '' ? "$alias." : '';
        $where = "({$prefix}sortpath >= :rangeStart AND {$prefix}sortpath < :rangeEnd";
        if ($this->maxDepth !== null) {
            $where .= " AND {$prefix}depth <= :maxDepth";
        }
        return $where . ")";
    }
}
