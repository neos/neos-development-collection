<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class ValueCopyAst extends OperationAst
{
    public function __construct(
        public AssignedObjectPathAst $assignedObjectPath
    ) {}
}
