<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class ValueAssignmentAst extends OperationAst
{
    public function __construct(
        public PathValueAst $pathValue
    ) {}
}
