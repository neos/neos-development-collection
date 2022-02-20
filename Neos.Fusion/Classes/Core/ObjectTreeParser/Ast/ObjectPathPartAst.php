<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class ObjectPathPartAst extends PathSegmentAst
{
    public function __construct(
        /** @psalm-readonly */
        public string $identifier
    ) {}
}
