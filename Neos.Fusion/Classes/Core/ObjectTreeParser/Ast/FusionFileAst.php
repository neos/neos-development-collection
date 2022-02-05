<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class FusionFileAst extends NodeAst
{
    /**
     * @var StatementListAst
     */
    protected $statementList;

    /**
     * @var ?string
     */
    protected $contextPathAndFileName;

    public function __construct(StatementListAst $statementList, ?string $contextPathAndFileName)
    {
        $this->statementList = $statementList;
        $this->contextPathAndFileName = $contextPathAndFileName;
    }

    public function getContextPathAndFileName(): ?string
    {
        return $this->contextPathAndFileName;
    }

    public function getStatementList(): StatementListAst
    {
        return $this->statementList;
    }
}
