<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class IncludeStatement extends AbstractStatement
{
    public function __construct(
        /** @psalm-readonly */
        public string $filePattern
    ) {
    }

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitIncludeStatement($this, ...$args);
    }
}
