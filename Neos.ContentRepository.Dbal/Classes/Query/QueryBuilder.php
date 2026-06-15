<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;

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
     * Extends {@see DBALQueryBuilder::innerJoin()} to allow to specify parameters for the subquery
     *
     * @return $this This QueryBuilder instance.
     */
    public function innerJoinWithStatement(string $fromAlias, SqlStatementInterface $joinStatement, string $alias, ?string $condition = null): self
    {
        if ($joinStatement->getParameters()->count() > 0) {
            $this->setParameters(
                array_merge($this->getParameters(), $joinStatement->getParameters()->toDbalParams()),
                array_merge($this->getParameterTypes(), $joinStatement->getParameters()->toDbalTypes()),
            );
        }

        $this->innerJoin(
            $fromAlias,
            $joinStatement->toSql(),
            $alias,
            $condition
        );

        return $this;
    }
}
