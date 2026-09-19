<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal\Query;

/**
 * Represents a SQL "table_factor"
 *
 * To be used inside FROM and JOIN "table_references"
 *
 *     SELECT *
 *       FROM {$sqlTableFactor->toSql()}
 *
 *     SELECT *
 *       FROM my_table
 *       JOIN {$sqlTableFactor->toSql()} ON a = b
 *
 * @internal
 */
interface SqlTableSubqueryInterface
{
    public function getParameters(): Parameters;

    /**
     * Returned SQL must be valid in syntax
     *
     *     table_subquery
     *
     * {@link https://mariadb.com/docs/server/reference/sql-statements/data-manipulation/selecting-data/joins/join-syntax}
     */
    public function toSql(): string;
}
