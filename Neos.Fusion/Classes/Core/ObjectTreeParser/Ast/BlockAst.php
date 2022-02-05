<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class BlockAst extends NodeAst
{
    /**
     * @var StatementListAst
     */
    protected $statementList;

    public function __construct(StatementListAst $statementList)
    {
        $this->statementList = $statementList;
    }

    public function getStatementList(): StatementListAst
    {
        return $this->statementList;
    }
}
