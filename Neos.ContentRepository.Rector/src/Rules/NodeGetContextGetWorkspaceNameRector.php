<?php

declare (strict_types=1);

namespace Neos\ContentRepository\Rector\Rules;

use Neos\ContentRepository\Rector\Utility\CodeSampleLoader;
use PhpParser\Node;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Rector\PostRector\Collector\NodesToAddCollector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class NodeGetContextGetWorkspaceNameRector extends AbstractRector
{
    use AllTraits;

    public function __construct(
        private readonly NodesToAddCollector $nodesToAddCollector
    )
    {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return CodeSampleLoader::fromFile('"NodeInterface::getContext()::getWorkspace()" will be rewritten', __CLASS__);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [\PhpParser\Node\Expr\MethodCall::class];
    }

    /**
     * @param \PhpParser\Node\Expr\MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        assert($node instanceof Node\Expr\MethodCall);
        // Node->getContext()->getWorkspaceName()
        if (!$this->isName($node->name, 'getWorkspaceName')) {
            return null;
        }
        if (!$node->var instanceof Node\Expr\MethodCall) {
            return null;
        }
        if (!$this->isName($node->var->name, 'getContext')) {
            return null;
        }

        if (!$this->isObjectType($node->var->var, new ObjectType(\Neos\ContentRepository\Projection\ContentGraph\Node::class))) {
            return null;
        }

        $nodeVar = $node->var->var;
        $this->nodesToAddCollector->addNodesBeforeNode(
            [
                self::assign(
                    'contentRepository',
                    $this->this_contentRepositoryRegistry_get(
                        $this->node_subgraphIdentity_contentRepositoryIdentifier($nodeVar)
                    )
                )
            ],
            $node
        );

        $workspace = $this->contentRepository_getWorkspaceFinder_findOneByCurrentContentStreamIdentifier(
            $this->node_subgraphIdentity_contentStreamIdentifier($nodeVar)
        );
        return $this->nodeFactory->createPropertyFetch($workspace, 'workspaceName');
    }
}
