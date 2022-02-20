<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class AssignedObjectPathAst extends NodeAst
{
    public function __construct(
        /** @psalm-readonly */
        public ObjectPathAst $objectPath,
        /** @psalm-readonly */
        public bool $isRelative
    ) {}
}
