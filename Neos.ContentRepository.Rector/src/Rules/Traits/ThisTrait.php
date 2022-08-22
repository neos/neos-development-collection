<?php
declare (strict_types=1);

namespace Neos\ContentRepository\Rector\Rules\Traits;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;

trait ThisTrait
{
    /**
     * @var \Rector\Core\PhpParser\Node\NodeFactory
     */
    protected $nodeFactory;

    private function this_contentRepositoryRegistry_subgraphForNode(Variable $nodeVariable): Expr
    {
        return $this->nodeFactory->createMethodCall(
            $this->this_contentRepositoryRegistry(),
            'subgraphForNode',
            [$nodeVariable]
        );
    }

    private function this_contentRepositoryRegistry_get(Expr $contentRepositoryIdentifier): Expr
    {
        return $this->nodeFactory->createMethodCall(
            $this->this_contentRepositoryRegistry(),
            'get',
            [
                $contentRepositoryIdentifier
            ]
        );
    }

    private function this_contentRepositoryRegistry(): Expr
    {
        return $this->nodeFactory->createPropertyFetch('this', 'contentRepositoryRegistry');
    }
}
