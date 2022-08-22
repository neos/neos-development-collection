<?php
declare (strict_types=1);

namespace Neos\ContentRepository\Rector\Rules\Traits;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;

trait ContentRepositoryTrait
{
    /**
     * @var \Rector\Core\PhpParser\Node\NodeFactory
     */
    protected $nodeFactory;

    private function contentRepository_getProjection(string $projectionClassName): Expr
    {
        return $this->nodeFactory->createMethodCall(
            new Variable('contentRepository'),
            'getProjection',
            [
                new Expr\ClassConstFetch(
                    new FullyQualified($projectionClassName),
                    new Identifier('class')
                )
            ]
        );
    }

    private function contentRepository_getWorkspaceFinder_findOneByCurrentContentStreamIdentifier(Expr $contentStreamIdentifier): Expr
    {
        return $this->nodeFactory->createMethodCall(
            $this->contentRepository_getWorkspaceFinder(),
            'findOneByCurrentContentStreamIdentifier',
            [
                $contentStreamIdentifier
            ]
        );
    }

    private function contentRepository_getWorkspaceFinder(): Expr
    {
        return $this->nodeFactory->createMethodCall(
            new Variable('contentRepository'),
            'getWorkspaceFinder',
            []
        );
    }
}
