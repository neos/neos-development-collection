<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
abstract class AbstractNode
{
    abstract public function visit(AstNodeVisitor $visitor, ...$args);
}
