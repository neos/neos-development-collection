<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class SimpleValue extends AbstractPathValue
{
    public function __construct(
        /** @psalm-readonly */
        public mixed $value
    ) {
    }

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitSimpleValue($this, ...$args);
    }
}
