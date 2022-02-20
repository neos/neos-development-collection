<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class ObjectPathAst extends NodeAst
{
    /**
     * @psalm-readonly
     * @var PathSegmentAst[]
     */
    public $segments;

    public function __construct(PathSegmentAst ...$segments)
    {
        $this->segments = $segments;
    }
}
