<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class FusionFile extends AbstractNode
{
    public function __construct(
        /** @psalm-readonly */
        public StatementList $statementList,
        /** @psalm-readonly */
        public ?string $contextPathAndFileName
    ) {
    }

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitFusionFile($this, ...$args);
    }
}
