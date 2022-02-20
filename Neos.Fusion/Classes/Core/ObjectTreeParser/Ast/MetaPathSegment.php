<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class MetaPathSegment extends AbstractPathSegment
{
    public function __construct(
        /** @psalm-readonly */
        public string $identifier
    ) {}

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitMetaPathSegment($this, ...$args);
    }
}
