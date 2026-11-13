<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\PhpstanRules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

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
        $expressionType = $scope->getType($node->expr);
        if (!$expressionType->isObject()->yes() || !$this->classNameIsWithinConfiguredNamespaces($expressionType->getObjectClassNames())) {
            return [];
        }
        return [
            RuleErrorBuilder::message("Casting objects of class " . implode(', ', $expressionType->getObjectClassNames())
                . " to string is not allowed.")->identifier('cr.noStringCasting')->build(),
        ];
    }

    /**
     * @param array<string> $classNames
     */
    private function classNameIsWithinConfiguredNamespaces(array $classNames): bool
    {
        foreach ($classNames as $className) {
            foreach ($this->namespacePrefixesWhichShouldBeEnforced as $namespacePrefix) {
                if (str_starts_with($className, $namespacePrefix . '\\')) {
                    return true;
                }
            }
        }
        return false;
    }
}
