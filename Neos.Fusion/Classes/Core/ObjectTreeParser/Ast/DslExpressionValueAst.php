<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class DslExpressionValueAst extends PathValueAst
{
    public function __construct(
        /** @psalm-readonly */
        public string $identifier,
        /** @psalm-readonly */
        public string $code
    ) {}
}
