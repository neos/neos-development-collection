<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class EelExpressionValue extends AbstractPathValue
{
    public function __construct(
        /** @psalm-readonly */
        public string $value
    ) {}

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitEelExpressionValue($this, ...$args);
    }
}
