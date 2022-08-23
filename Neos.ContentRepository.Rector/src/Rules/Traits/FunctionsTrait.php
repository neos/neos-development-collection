<?php
declare (strict_types=1);

namespace Neos\ContentRepository\Rector\Rules\Traits;

use PhpParser\Comment;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Nop;

trait FunctionsTrait
{
    /**
     * @var \Rector\Core\PhpParser\Node\NodeFactory
     */
    protected $nodeFactory;

    private function iteratorToArray(Expr $inner): Expr
    {
        return $this->nodeFactory->createFuncCall('iterator_to_array', [$inner]);
    }

    private function castToString(Expr $inner): Expr
    {
        return new Expr\Cast\String_($inner);
    }

    private static function assign(string $variableName, Expr $value): Assign
    {
        return new Assign(
            new Variable($variableName),
            $value
        );
    }

    private static function todoComment(string $commentText): Nop
    {
        return new Nop([
            'comments' => [
                new Comment('// TODO 9.0 migration: ' . $commentText)
            ]
        ]);
    }
}
