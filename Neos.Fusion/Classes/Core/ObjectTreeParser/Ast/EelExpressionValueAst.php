<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class EelExpressionValueAst extends PathValueAst
{
    public function __construct(
        /** @psalm-readonly */
        public string $value
    ) {}
}
