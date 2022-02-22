<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class Block extends AbstractNode
{
    public function __construct(
        /** @psalm-readonly */
        public StatementList $statementList
    ) {
    }

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitBlock($this, ...$args);
    }
}
