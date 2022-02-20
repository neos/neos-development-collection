<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class StatementListAst extends NodeAst
{
    /**
     * @psalm-readonly
     * @var StatementAst[]
     */
    public $statements = [];

    public function __construct(StatementAst ...$statements)
    {
        $this->statements = $statements;
    }
}
