<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal\Query;

/**
 * Utility to compose ad-hoc where conditions.
 *
 * See {@see SqlWhereConditionInterface}
 *
 * @internal
 */
final readonly class StaticWhereCondition implements SqlWhereConditionInterface
{
    private function __construct(
        private string $expectedAlias,
        private string $whereConditionSql,
    ) {
    }

    public static function fromString(string $expectedAlias, string $whereConditionSql): self
    {
        return new self(
            expectedAlias: $expectedAlias,
            whereConditionSql: $whereConditionSql,
        );
    }

    public function getParameters(): Parameters
    {
        return Parameters::create();
    }

    public function toWhereSql(string $alias): string
    {
        if ($alias !== $this->expectedAlias) {
            throw new \RuntimeException(sprintf('Static where condition "%s" only applies to alias "%s" requested "%s"', $this->whereConditionSql, $this->expectedAlias, $alias), 1782070256);
        }
        return $this->whereConditionSql;
    }
}
