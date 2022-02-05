<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

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
