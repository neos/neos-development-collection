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
final readonly class NodeSortPathsInCondition implements SqlWhereConditionInterface
{
    /**
     * @param array<NodeSortPath> $nodeSortPaths
     */
    private function __construct(
        private array $nodeSortPaths,
    ) {
    }

    /**
     * @param array<NodeSortPath> $nodeSortPaths
     * @return self
     */
    public static function forNodeSortPaths(array $nodeSortPaths): self
    {
        if ($nodeSortPaths === []) {
            throw new \InvalidArgumentException('NodeSortPaths to filter must not be empty', 1781553575);
        }
        return new self(
            $nodeSortPaths
        );
    }

    public function getParameters(): Parameters
    {
        $nodeSortPathStrings = array_map(fn ($nodeSortPath) => $nodeSortPath->value, $this->nodeSortPaths);
        return Parameters::create(
            count($this->nodeSortPaths) === 1
                ? Parameter::string('nodeSortPath', $nodeSortPathStrings[0])
                : Parameter::stringArray('nodeSortPaths', $nodeSortPathStrings)
        );
    }

    public function toWhereSql(string $alias): string
    {
        $prefix = $alias !== '' ? "$alias." : '';

        return count($this->nodeSortPaths) === 1
            ? "{$prefix}sortpath = :nodeSortPath"
            : "{$prefix}sortpath IN (:nodeSortPaths)";
    }
}
