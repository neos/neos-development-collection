<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class ObjectPath extends AbstractNode
{
    /**
     * @psalm-readonly
     * @var AbstractPathSegment[]
     */
    public $segments;

    public function __construct(AbstractPathSegment ...$segments)
    {
        $this->segments = $segments;
    }

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitObjectPath($this, ...$args);
    }
}
