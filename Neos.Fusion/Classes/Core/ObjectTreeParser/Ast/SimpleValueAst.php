<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class SimpleValueAst extends PathValueAst
{
    public function __construct(
        public mixed $value
    ) {}
}
