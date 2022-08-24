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

    private function contentRepository_getWorkspaceFinder_findOneByName(Expr $workspaceName)
    {
        return $this->nodeFactory->createMethodCall(
            $this->contentRepository_getWorkspaceFinder(),
            'findOneByName',
            [
                $workspaceName
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

    private function contentRepository_getContentGraph_findRootNodeAggregateByType(Expr $contentStreamIdentifier, Expr $nodeTypeName)
    {
        return $this->nodeFactory->createMethodCall(
            $this->contentRepository_getContentGraph(),
            'findRootNodeAggregateByType',
            [
                $contentStreamIdentifier,
                $nodeTypeName
            ]
        );
    }

    private function contentRepository_getContentGraph_getSubgraph(Expr $contentStreamIdentifier, Expr $dimensionSpacePoint, Expr $visibilityConstraints)
    {
        return $this->nodeFactory->createMethodCall(
            $this->contentRepository_getContentGraph(),
            'getSubgraph',
            [
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $visibilityConstraints
            ]
        );
    }

    private function contentRepository_getContentGraph(): Expr
    {
        return $this->nodeFactory->createMethodCall(
            new Variable('contentRepository'),
            'getContentGraph',
            []
        );
    }

}
