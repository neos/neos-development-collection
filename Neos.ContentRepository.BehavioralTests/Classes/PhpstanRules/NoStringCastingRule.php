<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\PhpstanRules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<\PhpParser\Node\Expr\Cast\String_>
 */
class NoStringCastingRule implements Rule
{
    /**
     * @var string[]
     */
    private array $namespacePrefixesWhichShouldBeEnforced = [
        'Neos\\ContentRepository\\Core',
        'Neos\\ContentGraph',
    ];

    public function getNodeType(): string
    {
        return \PhpParser\Node\Expr\Cast\String_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof \PhpParser\Node\Expr\Cast\String_);
        $expressionType = $scope->getType($node->expr);
        if (!$expressionType instanceof ObjectType || !$this->classNameIsWithinConfiguredNamespaces($expressionType->getClassName())) {
            return [];
        }
        return [
            RuleErrorBuilder::message("Casting objects of class {$expressionType->getClassName()} to string is not allowed.")->build(),
        ];
    }

    private function classNameIsWithinConfiguredNamespaces(string $className): bool
    {
        foreach ($this->namespacePrefixesWhichShouldBeEnforced as $namespacePrefix) {
            if (str_starts_with($className, $namespacePrefix . '\\')) {
                return true;
            }
        }
        return false;
    }
}
