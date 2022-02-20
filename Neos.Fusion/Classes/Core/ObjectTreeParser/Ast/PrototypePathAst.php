<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class PrototypePathAst extends PathSegmentAst
{
    public function __construct(
        public string $identifier
    ) {}
}
