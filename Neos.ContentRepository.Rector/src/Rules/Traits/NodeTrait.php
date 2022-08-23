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

    private function node_subgraphIdentity(Expr $nodeVariable): Expr
    {
        return $this->nodeFactory->createPropertyFetch($nodeVariable, 'subgraphIdentity');
    }

    private function node_subgraphIdentity_contentRepositoryIdentifier(Expr $nodeVariable)
    {
        return $this->nodeFactory->createPropertyFetch(
            $this->node_subgraphIdentity($nodeVariable),
            'contentRepositoryIdentifier'
        );
    }

    private function node_subgraphIdentity_contentStreamIdentifier(Expr $nodeVariable): Expr
    {
        return $this->nodeFactory->createPropertyFetch(
            $this->node_subgraphIdentity($nodeVariable),
            'contentStreamIdentifier'
        );
    }

    private function node_subgraphIdentity_dimensionSpacePoint(Expr $nodeVariable): Expr
    {
        return $this->nodeFactory->createPropertyFetch(
            $this->node_subgraphIdentity($nodeVariable),
            'dimensionSpacePoint'
        );
    }

    private function node_nodeAggregateIdentifier(Expr $nodeVariable): Expr
    {
        return $this->nodeFactory->createPropertyFetch(
            $nodeVariable,
            'nodeAggregateIdentifier'
        );
    }

    private function node_originDimensionSpacePoint(Expr $nodeVariable): Expr
    {
        return $this->nodeFactory->createPropertyFetch($nodeVariable, 'originDimensionSpacePoint');
    }

    private function node_originDimensionSpacePoint_toLegacyDimensionArray(Expr $nodeVariable): Expr
    {
        return $this->nodeFactory->createMethodCall(
            $this->node_originDimensionSpacePoint($nodeVariable),
            'toLegacyDimensionArray'
        );
    }

}
