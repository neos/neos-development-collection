<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\DBAL\Types\Type;

/**
 * @internal
 */
final class QueryBuilder extends DBALQueryBuilder
{
    public static function createForConnection(Connection $connection): self
    {
        return new self($connection);
    }

    /**
     * Extends {@see DBALQueryBuilder::setParameters()} to allow merging
     *
     * @return $this This QueryBuilder instance.
     */
    public function mergeParameters(Parameters $parameters): self
    {
        $this->mergeDbalParameters(
            $parameters->toDbalValues(),
            $parameters->toDbalTypes(),
        );
        return $this;
    }

    /**
     * Extends {@see DBALQueryBuilder::setParameters()} to allow merging
     *
     * @return $this This QueryBuilder instance.
     */
    public function mergeParametersFromBuilder(QueryBuilder $queryBuilder): self
    {
        $this->mergeDbalParameters(
            $queryBuilder->getParameters(),
            $queryBuilder->getParameterTypes(),
        );
        return $this;
    }

    /**
     * Extends {@see DBALQueryBuilder::from()} to allow to specify parameters for the subquery
     *
     * @return $this This QueryBuilder instance.
     */
    public function fromWithStatement(SqlStatementInterface $fromStatement, string $alias): self
    {
        $this->mergeParameters($fromStatement->getParameters());

        $this->from(
            $fromStatement->toSql(),
            $alias
        );

        return $this;
    }

    /**
     * Extends {@see DBALQueryBuilder::innerJoin()} to allow to specify parameters for the subquery
     *
     * @return $this This QueryBuilder instance.
     */
    public function innerJoinWithStatement(string $fromAlias, SqlStatementInterface $joinStatement, string $alias, ?string $condition = null): self
    {
        $this->mergeParameters($joinStatement->getParameters());

        $this->innerJoin(
            $fromAlias,
            $joinStatement->toSql(),
            $alias,
            $condition
        );

        return $this;
    }

    /**
     * @param array<string|int, mixed> $otherValues
     * @param array<string|int, int|string|Type|null> $otherTypes
     */
    private function mergeDbalParameters(array $otherValues, array $otherTypes): void
    {
        if ($otherValues === [] && $otherTypes === []) {
            return;
        }

        $existingValues = $this->getParameters();
        $existingTypes = $this->getParameterTypes();

        $mergedTypes = Parameters::mergeDbalTypes(
            $existingTypes,
            $otherTypes,
        );

        $mergedValues = Parameters::mergeDbalValues(
            $existingValues,
            $otherValues,
        );

        $this->setParameters(
            $mergedValues,
            $mergedTypes,
        );
    }
}
