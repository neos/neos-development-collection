<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class PrototypePathSegment extends AbstractPathSegment
{
    public function __construct(
        public string $identifier
    ) {}

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitPrototypePathSegment($this, ...$args);
    }
}
