<?php

declare (strict_types=1);
namespace Neos\ContentRepository\Rector\Rules;

use PhpParser\Comment;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Rector\PostRector\Collector\NodesToAddCollector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class NodeGetChildNodesRector extends AbstractRector
{
    public function __construct(
        private readonly NodesToAddCollector $nodesToAddCollector
    )
    {
    }

    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('"NodeInterface::getChildNodes()" will be rewritten', [new CodeSample(<<<'CODE_SAMPLE'
use Neos\ContentRepository\Projection\ContentGraph\Node;

class SomeClass
{
    public function run(Node $node)
    {
        foreach ($node->getChildNodes() as $node) {
        }
    }
}
CODE_SAMPLE
            , <<<'CODE_SAMPLE'
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

class SomeClass
{

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function run(Node $node)
    {
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        foreach ($subgraph->findChildNodes($node->nodeAggregateIdentifier) as $node) {
        }
    }
}
CODE_SAMPLE
        )]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Expr\MethodCall::class];
    }
    /**
     * @param \PhpParser\Node\Expr\MethodCall $node
     */
    public function refactor(Node $node) : ?Node
    {
        assert($node instanceof Node\Expr\MethodCall);

        if (!$this->isObjectType($node->var, new ObjectType(\Neos\ContentRepository\Projection\ContentGraph\Node::class))) {
            return null;
        }
        if (!$this->isName($node->name, 'getChildNodes')) {
            return null;
        }
        if (count($node->args) > 0) {
            throw new \RuntimeException('TODO - not supported right now');
        }


        $subgraphCall = $this->getSubgraphCall($node->var);
        $this->nodesToAddCollector->addNodesBeforeNode(
            [
                $subgraphCall,
                new Node\Stmt\Nop([
                    'comments' => [
                        new Comment('// TODO: (9.0 migration) Try to remove the iterator_to_array($nodes) call.')
                    ]
                ])
            ],
            $node
        );

        // iterator_to_array($subgraph->findChildNodes(__$node__->nodeAggregateIdentifier));
        return $this->iteratorToArray($this->nodeFactory->createMethodCall(
            'subgraph',
            'findChildNodes',
            [
                $this->nodeFactory->createPropertyFetch($node->var, 'nodeAggregateIdentifier')
            ]
        ));
    }


    /**
     * $subgraph = $this->contentRepositoryRegistry->subgraphForNode(__ $node __);
     *
     * @param Variable $nodeVariable
     * @return Assign
     */
    private function getSubgraphCall(Variable $nodeVariable): Assign
    {

        $subgraphForNodeCall = $this->nodeFactory->createMethodCall(
            $this->nodeFactory->createPropertyFetch('this', 'contentRepositoryRegistry'),
            'subgraphForNode',
             [$nodeVariable]
        );
        return new Assign(new Variable('subgraph'), $subgraphForNodeCall);
    }

    private function iteratorToArray(Node\Expr\MethodCall $createMethodCall): Node
    {

        $call = $this->nodeFactory->createFuncCall('iterator_to_array', [$createMethodCall]);
        $call->setDocComment(new Doc("/** Foo */"));
        return $call;
    }

}
