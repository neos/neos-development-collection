<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\PhpstanRules;

use Neos\ContentRepository\BehavioralTests\PhpstanRules\Utility\ClassClassification;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<Node\Expr\CallLike>
 */
class InternalMethodsNotAllowedOutsideContentRepositoryRule implements Rule
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider
    ) {
    }

    public function getNodeType(): string
    {
        return Node\Expr\CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\CallLike);

        // TODO CORE
        if ($scope->getNamespace() && str_starts_with($scope->getNamespace(), 'Neos\ContentRepository')) {
            // Core is allowed to call all namespaces
            // TODO !!! ONLY FROM WITHIN OWN PACKAGE!!!!
            return [];
        }
        if ($node instanceof Node\Expr\MethodCall) {
            $methodCallTargetClass = $scope->getType($node->var);
            if ($methodCallTargetClass instanceof ObjectType) {
                $targetClassName = $methodCallTargetClass->getClassName();
                if (!str_starts_with($targetClassName, 'Neos\ContentRepository')) {
                    return [];
                }

                // here, we know an OUTSIDE class is calling into Neos\ContentRepository Core -> only allowed to use
                // public API

                $targetClassReflection = $this->reflectionProvider->getClass($targetClassName);
                $classification = ClassClassification::fromClassReflection($targetClassReflection);
                if (!$classification->isApi && $node->name instanceof Node\Identifier) {
                    return [
                        RuleErrorBuilder::message(
                            sprintf(
                                'The internal method "%s::%s" is called.',
                                $targetClassName,
                                $node->name->toString()
                            )
                        )->build(),
                    ];
                }
            }
        }
        return [];
    }
}
