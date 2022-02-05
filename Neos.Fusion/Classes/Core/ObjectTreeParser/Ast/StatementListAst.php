<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

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
