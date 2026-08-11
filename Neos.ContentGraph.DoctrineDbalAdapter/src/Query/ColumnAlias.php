<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Query;

use Flowpack\QueryObjectBuilder\MySQL\Builder\Exp;
use Flowpack\QueryObjectBuilder\MySQL\Builder\SqlBuilder;

/**
 * Todo i found out later that one can use ->select()->as()->select()->as() but i find this pattern to verbose
 */
class ColumnAlias implements Exp
{
    public function __construct(
        private Exp $exp,
        private string $alias
    ) {
    }

    public static function alias(
        Exp $exp,
        string $alias,
    ): self {
        if ($alias === '') {
            throw new \RuntimeException('Must not be empty', 1783012425);
        }
        return new self(
            exp: $exp,
            alias: $alias,
        );
    }

    public function writeSql(SqlBuilder $sb): void
    {
        $this->exp->writeSql($sb);
        $sb->writeString(' AS ' . $this->alias);
    }
}
