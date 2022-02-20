<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class ObjectDefinitionAst extends StatementAst
{
    public function __construct(
        /** @psalm-readonly */
        public ObjectPathAst $path,
        /** @psalm-readonly */
        public ValueAssignmentAst|ValueCopyAst|ValueUnsetAst|null $operation,
        /** @psalm-readonly */
        public ?BlockAst $block = null
    ) {}
}
