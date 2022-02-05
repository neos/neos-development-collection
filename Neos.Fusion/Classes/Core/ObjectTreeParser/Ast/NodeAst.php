<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

abstract class NodeAst
{
    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        $classWithoutNamespace = basename(str_replace('\\', '/', static::class));

        return $visitor->{'visit' . $classWithoutNamespace}($this, ...$args);
    }
}
