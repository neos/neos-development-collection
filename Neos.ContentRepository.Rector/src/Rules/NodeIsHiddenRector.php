<?php

declare (strict_types=1);
namespace Neos\ContentRepository\Rector\Rules;

use Neos\ContentRepository\Projection\NodeHiddenState\NodeHiddenStateProjection;
use Neos\ContentRepository\Rector\Utility\CodeSampleLoader;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Rector\PostRector\Collector\NodesToAddCollector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class NodeIsHiddenRector extends AbstractRector
{
    use AllTraits;

    public function __construct(
        private readonly NodesToAddCollector $nodesToAddCollector
    )
    {
    }

    public function getRuleDefinition() : RuleDefinition
    {
        return CodeSampleLoader::fromFile('"NodeInterface::isHidden()" will be rewritten', __CLASS__);
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
        if (!$this->isName($node->name, 'isHidden')) {
            return null;
        }

        $getContentRepository = $this->this_contentRepositoryRegistry_get(
            $this->node_subgraphIdentity_contentRepositoryIdentifier($node->var)
        );
        $getNodeHiddenStateFinder = $this->contentRepository_getProjection(NodeHiddenStateProjection::class);
        $getHiddenState = $this->nodeHiddenStateFinder_findHiddenState($node->var);

        $this->nodesToAddCollector->addNodesBeforeNode(
            [
                self::assign('contentRepository', $getContentRepository),
                self::assign('nodeHiddenStateFinder', $getNodeHiddenStateFinder),
                self::assign('hiddenState', $getHiddenState),
            ],
            $node
        );

        return $this->nodeFactory->createMethodCall(
            new Variable('hiddenState'),
            'isHidden'
        );
    }
}
