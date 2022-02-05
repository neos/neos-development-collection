<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class AssignedObjectPathAst extends NodeAst
{
    /**
     * @var ObjectPathAst
     */
    protected $objectPath;

    /**
     * @var bool
     */
    protected $isRelative;

    /**
     * @param ObjectPathAst $objectPath
     * @param bool $isRelative
     */
    public function __construct(ObjectPathAst $objectPath, bool $isRelative)
    {
        $this->objectPath = $objectPath;
        $this->isRelative = $isRelative;
    }

    public function getObjectPath(): ObjectPathAst
    {
        return $this->objectPath;
    }

    public function isRelative(): bool
    {
        return $this->isRelative;
    }
}
