<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class ObjectPathAst extends NodeAst
{
    /**
     * @var PathSegmentAst[]
     */
    protected $segments;

    public function __construct(PathSegmentAst ...$segments)
    {
        $this->segments = $segments;
    }

    /**
     * @return PathSegmentAst[]
     */
    public function getSegments(): array
    {
        return $this->segments;
    }
}
