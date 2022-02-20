<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class BlockAst extends NodeAst
{
    public function __construct(
        /** @psalm-readonly */
        public StatementListAst $statementList
    ) {}
}
