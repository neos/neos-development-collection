<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class StatementListAst extends NodeAst
{
    /**
     * @var StatementAst[]
     */
    protected $statements = [];

    public function __construct(StatementAst ...$statements)
    {
        $this->statements = $statements;
    }

    /**
     * @return StatementAst[]
     */
    public function getStatements(): array
    {
        return $this->statements;
    }
}
