<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class ValueAssignment extends AbstractOperation
{
    public function __construct(
        /** @psalm-readonly */
        public AbstractPathValue $pathValue
    ) {
    }

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitValueAssignment($this, ...$args);
    }
}
