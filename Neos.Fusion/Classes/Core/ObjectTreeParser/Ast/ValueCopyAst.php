<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class ValueCopyAst extends OperationAst
{
    /**
     * @var AssignedObjectPathAst
     */
    protected $assignedObjectPath;

    public function __construct(AssignedObjectPathAst $assignedObjectPath)
    {
        $this->assignedObjectPath = $assignedObjectPath;
    }

    public function getAssignedObjectPath(): AssignedObjectPathAst
    {
        return $this->assignedObjectPath;
    }
}
