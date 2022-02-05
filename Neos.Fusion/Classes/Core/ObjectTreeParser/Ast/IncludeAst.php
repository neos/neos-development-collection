<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class IncludeAst extends StatementAst
{
    /**
     * @var string
     */
    protected $filePattern;

    public function __construct(string $filePattern)
    {
        $this->filePattern = $filePattern;
    }

    public function getFilePattern(): string
    {
        return $this->filePattern;
    }
}
