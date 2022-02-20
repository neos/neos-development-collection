<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class DslExpressionValue extends AbstractPathValue
{
    public function __construct(
        /** @psalm-readonly */
        public string $identifier,
        /** @psalm-readonly */
        public string $code
    ) {}

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitDslExpressionValue($this, ...$args);
    }
}
