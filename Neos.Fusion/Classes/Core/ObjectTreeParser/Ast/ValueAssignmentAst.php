<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

class ValueAssignmentAst extends OperationAst
{
    /**
     * @var PathValueAst
     */
    protected $pathValue;

    /**
     * @param PathValueAst $pathValue
     */
    public function __construct(PathValueAst $pathValue)
    {
        $this->pathValue = $pathValue;
    }

    /**
     * @return PathValueAst
     */
    public function getPathValue(): PathValueAst
    {
        return $this->pathValue;
    }
}
