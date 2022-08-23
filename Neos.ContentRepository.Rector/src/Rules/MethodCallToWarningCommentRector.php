<?php

declare (strict_types=1);

namespace Neos\ContentRepository\Rector\Rules;

use Neos\ContentRepository\Rector\Utility\CodeSampleLoader;
use Neos\ContentRepository\Rector\ValueObject\MethodCallToWarningComment;
use PhpParser\Node;
use PHPStan\Type\ObjectType;
use Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use Rector\Core\Rector\AbstractRector;
use Rector\PostRector\Collector\NodesToAddCollector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Webmozart\Assert\Assert;

final class MethodCallToWarningCommentRector extends AbstractRector implements ConfigurableRectorInterface
{
    use AllTraits;

    /**
     * @var MethodCallToWarningComment[]
     */
    private array $methodCallsToWarningComments = [];

    public function __construct(
        private readonly NodesToAddCollector $nodesToAddCollector
    )
    {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return CodeSampleLoader::fromFile('"Warning comments for various non-supported use cases', __CLASS__);
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
        foreach ($this->methodCallsToWarningComments as $methodCallToWarningComment) {
            if (!$this->isName($node->name, $methodCallToWarningComment->methodName)) {
                continue;
            }
            if (!$this->isObjectType($node->var, new ObjectType($methodCallToWarningComment->objectType))) {
                continue;
            }

            $this->nodesToAddCollector->addNodesBeforeNode(
                [
                    self::todoComment($methodCallToWarningComment->warningMessage)
                ],
                $node
            );

            return $node;
        }
        return null;
    }


    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration) : void
    {
        Assert::allIsAOf($configuration, MethodCallToWarningComment::class);
        $this->methodCallsToWarningComments = $configuration;
    }
}
