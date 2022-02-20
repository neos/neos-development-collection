<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class FusionFileAst extends NodeAst
{
    public function __construct(
        /** @psalm-readonly */
        public StatementListAst $statementList,
        /** @psalm-readonly */
        public ?string $contextPathAndFileName
    ) {}
}
