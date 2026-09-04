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
        private Parameters $parameters,
    ) {
    }

    /**
     * The parameters are optional: a caller that executes the statement itself may bind the placeholders
     * directly. They MUST be passed whenever the condition ends up inside a {@see QueryBuilder}, which
     * collects the parameters of the subqueries it inlines and has no other way of learning about them.
     */
    public static function fromString(string $expectedAlias, string $whereConditionSql, ?Parameters $parameters = null): self
    {
        return new self(
            expectedAlias: $expectedAlias,
            whereConditionSql: $whereConditionSql,
            parameters: $parameters ?? Parameters::create(),
        );
    }

    public function getParameters(): Parameters
    {
        return $this->parameters;
    }

    public function toWhereSql(string $alias): string
    {
        if ($alias !== $this->expectedAlias) {
            throw new \RuntimeException(sprintf('Static where condition "%s" only applies to alias "%s" requested "%s"', $this->whereConditionSql, $this->expectedAlias, $alias), 1782070256);
        }
        return $this->whereConditionSql;
    }
}
