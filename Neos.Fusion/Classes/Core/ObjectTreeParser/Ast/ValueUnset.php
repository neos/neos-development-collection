<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class ValueUnset extends AbstractOperation
{
    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitValueUnset($this, ...$args);
    }
}
