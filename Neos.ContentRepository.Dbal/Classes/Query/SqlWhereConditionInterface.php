<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal\Query;

/**
 * Represents a SQL "where_condition"
 *
 * To be used within a where condition:
 *
 *     SELECT * FROM my_table
 *       WHERE {$sqlWhereCondition->toWhereSql('')}
 *
 * With a possible alias
 *
 *     SELECT * FROM my_table AS mt
 *       WHERE {$sqlWhereCondition->toWhereSql('mt')}
 *
 * @internal
 */
interface SqlWhereConditionInterface
{
    public function getParameters(): Parameters;

    /**
     * Returned SQL must be valid in syntax
     *
     *     [WHERE where_condition]
     *
     * {@link https://mariadb.com/docs/server/reference/sql-statements/data-manipulation/selecting-data/select}
     */
    public function toWhereSql(string $alias): string;
}
