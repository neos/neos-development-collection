<?php

declare (strict_types=1);
namespace Neos\ContentRepository\Rector\Rules;

use Neos\ContentRepository\Rector\Legacy\LegacyContextStub;
use Neos\ContentRepository\Rector\Utility\CodeSampleLoader;
use PhpParser\Node;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Rector\PostRector\Collector\NodesToAddCollector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ContextFactoryToLegacyContextStubRector extends AbstractRector
{
    use AllTraits;

    public function __construct(
        private readonly NodesToAddCollector $nodesToAddCollector
    )
    {
    }

    public function getRuleDefinition() : RuleDefinition
    {
        return CodeSampleLoader::fromFile('"ContextFactory::create()" will be rewritten.', __CLASS__);
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

        if (!$this->isObjectType($node->var, new ObjectType('Neos\ContentRepository\Domain\Service\ContextFactoryInterface'))
            && !$this->isObjectType($node->var, new ObjectType('Neos\ContentRepository\Domain\Service\ContextFactory'))
            && !$this->isObjectType($node->var, new ObjectType('Neos\Neos\Domain\Service\ContentContextFactory'))
        ) {
            return null;
        }
        if (!$this->isName($node->name, 'create')) {
            return null;
        }

        return new Node\Expr\New_(
            // TODO clean up
            new Node\Name('\\' . LegacyContextStub::class),
            $node->args
        );
    }
}
