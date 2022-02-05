<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

class MetaPathAst extends PathSegmentAst
{
    /**
     * @var string
     */
    protected $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
