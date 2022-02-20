<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class StatementList extends AbstractNode
{
    /**
     * @psalm-readonly
     * @var AbstractStatement[]
     */
    public $statements = [];

    public function __construct(AbstractStatement ...$statements)
    {
        $this->statements = $statements;
    }

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitStatementList($this, ...$args);
    }
}
