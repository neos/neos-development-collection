<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\PhpstanRules;

use Neos\ContentRepository\BehavioralTests\PhpstanRules\Utility\ClassClassification;
use PhpParser\Node;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<\PhpParser\Node\Stmt\Class_>
 */
class ApiOrInternalAnnotationRule implements Rule
{
    /**
     * @var string[]
     */
    private $namespacePrefixesWhichShouldBeEnforced = [
        'Neos\ContentRepository\Core',
        'Neos\ContentGraph',
    ];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider
    ) {
    }

    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof \PhpParser\Node\Stmt\Class_);

        if (!$node->namespacedName) {
            return [];
        }

        // We only want to enfore @api / @internal in the certain packages
        if (!$this->nameIsWithinConfiguredNamespaces($node->namespacedName)) {
            return [];
        }

        $class = $this->reflectionProvider->getClass($node->namespacedName->toString());

        $classification = ClassClassification::fromClassReflection($class);
        if (!$classification->isInternal && !$classification->isApi) {
            return [
                RuleErrorBuilder::message(
                    'Class needs @api or @internal annotation.'
                )->build(),
            ];
        }
        return [];
    }

    private function nameIsWithinConfiguredNamespaces(?Node\Name $namespacedName): bool
    {
        if (!$namespacedName) {
            return false;
        }
        foreach ($this->namespacePrefixesWhichShouldBeEnforced as $namespacePrefix) {
            if (str_starts_with($namespacedName->toString(), $namespacePrefix)) {
                return true;
            }
        }

        return false;
    }
}
