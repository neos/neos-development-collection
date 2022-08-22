<?php

declare (strict_types=1);

namespace Neos\ContentRepository\Rector\Rules;

use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use Rector\Core\NodeManipulator\ClassInsertManipulator;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

// Modelled after https://raw.githubusercontent.com/sabbelasichon/typo3-rector/main/src/Rector/v10/v2/InjectEnvironmentServiceIfNeededInResponseRector.php
final class InjectContentRepositoryRegistryIfNeededRector extends AbstractRector
{
    public function __construct(
        private readonly ClassInsertManipulator $classInsertManipulator,
    )
    {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isContentRepositoryRegistryInUse($node)) {
            return null;
        }

        // already added
        if ($node->getProperty('contentRepositoryRegistry')) {
            return null;
        }

        $property = $this->createInjectedProperty('contentRepositoryRegistry', new FullyQualified(ContentRepositoryRegistry::class));
        $this->classInsertManipulator->addAsFirstMethod($node, $property);

        return $node;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Inject contentRepositoryRegistry if needed', [
            new CodeSample(
                <<<'CODE_SAMPLE'
TODO
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
TODO
CODE_SAMPLE
            ),
        ]);
    }

    private function isContentRepositoryRegistryInUse(Class_ $class): bool
    {
        $inUse = false;
        $this->traverseNodesWithCallable($class->stmts, function (Node $node) use (
            &$inUse
        ): ?PropertyFetch {
            if (!$node instanceof PropertyFetch) {
                return null;
            }

            // $this->contentRepositoryRegistry found somewhere in class
            if ($this->isName($node->name, 'contentRepositoryRegistry')) {
                if ($node->var instanceof Node\Expr\Variable && $node->var->name === 'this') {
                    $inUse = true;
                }
            }

            return $node;
        });
        return $inUse;
    }

    private function createInjectedProperty(string $propertyName, FullyQualified $type): Property
    {
        return new Property(Class_::MODIFIER_PROTECTED, [
            new Node\Stmt\PropertyProperty($propertyName)
        ], [], $type, [
            new Node\AttributeGroup([
                new Node\Attribute(
                    new Node\Name('Flow\Inject')
                )
            ])
        ]);
    }
}
