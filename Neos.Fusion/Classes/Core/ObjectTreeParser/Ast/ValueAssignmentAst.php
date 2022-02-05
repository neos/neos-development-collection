<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
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
