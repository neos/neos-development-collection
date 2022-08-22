<?php
declare (strict_types=1);

namespace Neos\ContentRepository\Rector\Rules\Traits;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;

trait NodeTrait
{
    /**
     * @var \Rector\Core\PhpParser\Node\NodeFactory
     */
    protected $nodeFactory;

    private function node_subgraphIdentity(Variable $nodeVariable): Expr
    {
        return $this->nodeFactory->createPropertyFetch($nodeVariable, 'subgraphIdentity');
    }


    private function node_subgraphIdentity_contentRepositoryIdentifier(Variable $nodeVariable)
    {
        return $this->nodeFactory->createPropertyFetch(
            $this->node_subgraphIdentity($nodeVariable),
            'contentRepositoryIdentifier'
        );
    }

    private function node_subgraphIdentity_contentStreamIdentifier(Variable $nodeVariable): Expr
    {
        return $this->nodeFactory->createPropertyFetch(
            $this->node_subgraphIdentity($nodeVariable),
            'contentStreamIdentifier'
        );
    }

    private function node_subgraphIdentity_dimensionSpacePoint(Variable $nodeVariable): Expr
    {
        return $this->nodeFactory->createPropertyFetch(
            $this->node_subgraphIdentity($nodeVariable),
            'dimensionSpacePoint'
        );
    }

    private function node_nodeAggregateIdentifier(Variable $nodeVariable): Expr
    {
        return $this->nodeFactory->createPropertyFetch(
            $nodeVariable,
            'nodeAggregateIdentifier'
        );
    }
}
