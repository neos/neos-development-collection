<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class MetaPathAst extends PathSegmentAst
{
    public function __construct(
        /** @psalm-readonly */
        public string $identifier
    ) {}
}
