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
     * Extends {@see DBALQueryBuilder::from()} to allow to specify parameters for the subquery
     *
     * @return $this This QueryBuilder instance.
     */
    public function fromTableSubquery(SqlTableSubqueryInterface $tableSubquery, string $alias): self
    {
        $this->mergeParameters($tableSubquery->getParameters());

        $this->from(
            $tableSubquery->toSql(),
            $alias
        );

        return $this;
    }

    /**
     * Extends {@see DBALQueryBuilder::innerJoin()} to allow to specify parameters for the subquery
     *
     * @return $this This QueryBuilder instance.
     */
    public function innerJoinTableSubquery(string $fromAlias, SqlTableSubqueryInterface $tableSubquery, string $alias, ?string $condition = null): self
    {
        $this->mergeParameters($tableSubquery->getParameters());

        $this->innerJoin(
            $fromAlias,
            $tableSubquery->toSql(),
            $alias,
            $condition
        );

        return $this;
    }

    /**
     * Extends {@see DBALQueryBuilder::where()} to allow to specify parameters for the condition
     *
     * @return $this This QueryBuilder instance.
     */
    public function whereCondition(string $alias, SqlWhereConditionInterface $whereCondition): self
    {
        $this->mergeParameters($whereCondition->getParameters());

        $this->where(
            $whereCondition->toWhereSql($alias)
        );

        return $this;
    }


    /**
     * Extends {@see DBALQueryBuilder::andWhere()} to allow to specify parameters for the condition
     *
     * @return $this This QueryBuilder instance.
     */
    public function andWhereCondition(SqlWhereConditionInterface $whereCondition, string $alias): self
    {
        $this->mergeParameters($whereCondition->getParameters());

        $this->andWhere(
            $whereCondition->toWhereSql($alias)
        );

        return $this;
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
