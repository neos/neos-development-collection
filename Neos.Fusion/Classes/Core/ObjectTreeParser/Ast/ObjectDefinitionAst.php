<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class ObjectDefinitionAst extends StatementAst
{
    /**
     * @var ObjectPathAst
     */
    protected $path;

    /**
     * @var ValueAssignmentAst|ValueUnsetAst|ValueCopyAst
     */
    protected $operation;

    /**
     * @var ?BlockAst
     */
    protected $block;

    /**
     * @param ValueAssignmentAst|ValueCopyAst|ValueUnsetAst|null $operation
     */
    public function __construct(ObjectPathAst $path, $operation, ?BlockAst $block = null)
    {
        $this->path = $path;
        $this->operation = $operation;
        $this->block = $block;
    }

    /**
     * @return ObjectPathAst
     */
    public function getPath(): ObjectPathAst
    {
        return $this->path;
    }

    /**
     * @return ValueAssignmentAst|ValueCopyAst|ValueUnsetAst
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @return BlockAst|null
     */
    public function getBlock(): ?BlockAst
    {
        return $this->block;
    }
}
