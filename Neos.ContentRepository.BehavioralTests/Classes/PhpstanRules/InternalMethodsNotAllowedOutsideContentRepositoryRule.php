<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\PhpstanRules;

use Neos\ContentRepository\BehavioralTests\PhpstanRules\Utility\ClassClassification;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

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
        if (
            $scope->getNamespace()
            && (
                str_starts_with($scope->getNamespace(), 'Neos\ContentRepository\Core')
                || str_starts_with($scope->getNamespace(), 'Neos\ContentRepository\Dbal')
                || str_starts_with($scope->getNamespace(), 'Neos\ContentGraph\DoctrineDbalAdapter')
                || str_starts_with($scope->getNamespace(), 'Neos\ContentRepository\BehavioralTests')
                || str_starts_with($scope->getNamespace(), 'Neos\ContentRepository\Export')
                || str_starts_with($scope->getNamespace(), 'Neos\ContentRepository\LegacyNodeMigration')
                || str_starts_with($scope->getNamespace(), 'Neos\ContentRepository\StructureAdjustment')
                // We don't limit ourselves to the @api in the CRUpgrades and thus violate our own internal restrictions. But this is part of the deal.
                || str_starts_with($scope->getNamespace(), 'Neos\ContentRepositoryRegistry\Upgrade\\')
            )
        ) {
            // todo this rule was intended to enforce the internal annotations from the Neos\ContentRepository\Core from all call sites.
            // this is currently not achievable and thus we grant a few packages BUT NOT NEOS.NEOS free access.
            // that is a good compromise between having this rule not enabled at all or cluttering everything with a baseline.
            return [];
        }
        if ($node instanceof Node\Expr\MethodCall) {
            $methodCallTargetClass = $scope->getType($node->var);
            if ($methodCallTargetClass->isObject()->yes()) {
                foreach ($methodCallTargetClass->getObjectClassNames() as $targetClassName) {
                    // currently only access to methods on the cr core is protected.
                    if (!str_starts_with($targetClassName, 'Neos\ContentRepository\Core')) {
                        continue;
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
                            )->identifier('neos.cr.internal')->build(),
                        ];
                    }
                }
            }
        }
        return [];
    }
}
