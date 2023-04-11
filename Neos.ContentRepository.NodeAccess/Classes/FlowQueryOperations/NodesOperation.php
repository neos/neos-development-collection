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
use Neos\Eel\FlowQuery\OperationInterface;

/**
 * "nodes" operation working on References
 *
 * This operation can be used to access the node(s) of reference(s):
 *
 *     ${q(node).references().nodes().property('title')}
 *
 * @see ReferencesOperation, BackReferencesOperation
 * @api To be used in Fusion, for PHP code {@see References::getNodes()} should be used instead
 */
final class NodesOperation implements OperationInterface
{

    public function canEvaluate($context): bool
    {
        return count($context) === 0 || (isset($context[0]) && $context[0] instanceof Reference);
    }

    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        $output = [];
        foreach ($flowQuery->getContext() as $element) {
            if (!$element instanceof Reference) {
                continue;
            }
            $output[] = $element->node;
        }
        $flowQuery->setContext($output);
    }

    public static function getShortName(): string
    {
        return 'nodes';
    }

    public static function getPriority(): int
    {
        return 100;
    }

    public static function isFinal(): bool
    {
        return false;
    }
}
