<?php
namespace Neos\ContentRepository\NodeAccess\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\OperationInterface;

/**
 * Used to access properties of a ContentRepository Reference
 *
 * This operation can be used to return the value of a node reference:
 *
 *     ${q(node).references("someReferenceName").property("somePropertyName")}
 *
 * @see ReferencesOperation, BackReferencesOperation
 * @api To be used in Fusion, for PHP code {@see Reference::properties} should be used instead
 */
final class ReferencePropertyOperation implements OperationInterface
{

    public function canEvaluate($context): bool
    {
        return (isset($context[0]) && $context[0] instanceof Reference);
    }

    /**
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): mixed
    {
        if (empty($arguments[0])) {
            throw new FlowQueryException('missing property name argument for the referenceProperty() operation', 1680342925);
        }
        /** @var array<int,mixed> $context */
        $context = $flowQuery->getContext();
        $element = $context[0] ?? null;
        if (!$element instanceof Reference) {
            return null;
        }
        return $element->properties[$arguments[0]];
    }

    public static function getShortName(): string
    {
        return 'referenceProperty';
    }

    public static function getPriority(): int
    {
        return 100;
    }

    public static function isFinal(): bool
    {
        return true;
    }
}
