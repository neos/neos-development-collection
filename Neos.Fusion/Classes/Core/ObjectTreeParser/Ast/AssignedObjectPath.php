<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class AssignedObjectPath extends AbstractNode
{
    public function __construct(
        /** @psalm-readonly */
        public ObjectPath $objectPath,
        /** @psalm-readonly */
        public bool $isRelative
    ) {
    }

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitAssignedObjectPath($this, ...$args);
    }
}
