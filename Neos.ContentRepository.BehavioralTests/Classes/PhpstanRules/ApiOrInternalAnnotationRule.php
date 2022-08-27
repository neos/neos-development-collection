<?php
declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\PhpstanRules;

use Neos\ContentRepository\BehavioralTests\PhpstanRules\Utility\ClassClassification;
use PhpParser\Node;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

class ApiOrInternalAnnotationRule implements Rule
{
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

        if (!str_starts_with($node->namespacedName->toString(), 'Neos\\ContentRepository')) {
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
}
