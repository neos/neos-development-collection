<?php
declare (strict_types=1);

namespace Neos\ContentRepository\Rector\Rules\Traits;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;

trait NodeHiddenStateFinderTrait
{
    use NodeTrait;

    /**
     * @var \Rector\Core\PhpParser\Node\NodeFactory
     */
    protected $nodeFactory;
    
    private function nodeHiddenStateFinder_findHiddenState(Variable $nodeVariable): Expr
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
}
