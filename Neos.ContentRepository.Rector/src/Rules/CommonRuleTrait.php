<?php
declare (strict_types=1);

namespace Neos\ContentRepository\Rector\Rules;

use PhpParser\Comment;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Nop;

trait CommonRuleTrait
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


    private function contentRepository_getProjection(string $projectionClassName)
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

    private function node_subgraphIdentity(Variable $nodeVariable)
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

    private function node_subgraphIdentity_contentStreamIdentifier(Variable $nodeVariable)
    {
        return $this->nodeFactory->createPropertyFetch(
            $this->node_subgraphIdentity($nodeVariable),
            'contentStreamIdentifier'
        );
    }

    private function node_subgraphIdentity_dimensionSpacePoint(Variable $nodeVariable)
    {
        return $this->nodeFactory->createPropertyFetch(
            $this->node_subgraphIdentity($nodeVariable),
            'dimensionSpacePoint'
        );
    }

    private function node_nodeAggregateIdentifier(Variable $nodeVariable)
    {
        return $this->nodeFactory->createPropertyFetch(
            $nodeVariable,
            'nodeAggregateIdentifier'
        );
    }


    private function nodeHiddenStateFinder_findHiddenState(Variable $nodeVariable)
    {
        return $this->nodeFactory->createMethodCall(
            new Variable('nodeHiddenStateFinder'),
            'findHiddenState',
            [
                $this->node_subgraphIdentity_contentStreamIdentifier($nodeVariable),
                $this->node_subgraphIdentity_dimensionSpacePoint($nodeVariable),
                $this->node_nodeAggregateIdentifier($nodeVariable),
            ]
        );
    }


    private function this_contentRepositoryRegistry(): Expr
    {
        return $this->nodeFactory->createPropertyFetch('this', 'contentRepositoryRegistry');
    }

    private function iteratorToArray(Expr $inner): Expr
    {
        return $this->nodeFactory->createFuncCall('iterator_to_array', [$inner]);
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
