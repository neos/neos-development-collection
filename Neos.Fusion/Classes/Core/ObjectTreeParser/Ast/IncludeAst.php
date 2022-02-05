<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

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
