<?php

namespace Neos\Fusion\Migrations\EelExpression;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\Ast\EelExpressionValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueAssignment;
use Neos\Fusion\Core\ObjectTreeParser\MergedArrayTree;
use Neos\Fusion\Core\ObjectTreeParser\MergedArrayTreeVisitor;

/**
 * @Flow\Proxy(false)
 * @internal
 */
final class EelExpressionPathBuilderVisitor extends MergedArrayTreeVisitor
{
    public function __construct(private readonly EelExpressionPositions $eelExpressionPositions)
    {
        parent::__construct(
            new MergedArrayTree([]),
            fn () => false,
            fn () => [],
        );
    }

    public function visitValueAssignment(ValueAssignment $valueAssignment, ?array $currentPath = null)
    {
        $currentPath ?? throw new \BadMethodCallException('$currentPath is required.');

        // send currentPath to eel expression value
        $valueAssignment->pathValue->visit($this, $currentPath);
    }

    public function visitEelExpressionValue(EelExpressionValue $eelExpressionValue, ?array $currentPath = null)
    {
        $eelExpressionPosition = $this->eelExpressionPositions->byEelExpressionValue($eelExpressionValue);
        if ($eelExpressionPosition) {
            $eelExpressionPosition->fusionPath = EelExpressionFusionPath::create($currentPath);
        }
    }
}
