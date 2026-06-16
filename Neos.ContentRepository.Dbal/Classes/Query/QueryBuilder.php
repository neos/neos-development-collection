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
     * Extends {@see DBALQueryBuilder::setParameter()} to allow merging
     *
     * @return $this This QueryBuilder instance.
     */
    public function mergeParameters(Parameters $parameters): self
    {
        $this->mergeDbalParameters(
            $parameters->toDbalParams(),
            $parameters->toDbalTypes(),
        );
        return $this;
    }

    /**
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
     * @param array<string|int, mixed> $otherParams
     * @param array<string|int, int|string|Type|null> $otherTypes
     */
    private function mergeDbalParameters(array $otherParams, array $otherTypes): void
    {
        if ($otherParams === [] && $otherTypes === []) {
            return;
        }

        $existingParams = $this->getParameters();
        $existingTypes = $this->getParameterTypes();

        $intersectingExistingTypes = array_intersect_key($existingTypes, $otherTypes);

        foreach ($intersectingExistingTypes as $existingKey => $existingType) {
            $otherType = $otherTypes[$existingKey];
            if ($otherType !== $existingType) {
                throw AmbiguousParametersGiven::becauseParameterIsAlreadyDefinedWithType(
                    (string)$existingKey,
                    $existingType,
                    $otherType
                );
            }
        }

        $intersectingExistingValues = array_intersect_key($existingParams, $otherParams);

        foreach ($intersectingExistingValues as $existingKey => $existingValue) {
            $otherValue = $otherParams[$existingKey];
            if ($otherValue !== $existingValue) {
                throw AmbiguousParametersGiven::becauseParameterIsAlreadyDefinedWithValue(
                    (string)$existingKey,
                    $existingValue,
                    $otherValue
                );
            }
        }

        $this->setParameters(
            array_merge($existingParams, $otherParams),
            array_merge($existingTypes, $otherTypes),
        );
    }
}
