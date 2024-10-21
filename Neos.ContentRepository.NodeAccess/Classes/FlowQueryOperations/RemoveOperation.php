<?php

declare(strict_types=1);

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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\OperationInterface;

/**
 * Removes the given Node from the current context.
 *
 * The operation accepts one argument that may be an Array, a FlowQuery
 * or an Object.
 *
 * !!! This is a Node specific implementation of the generic `remove` operation!!!
 *
 * The result is an array of {@see Node} instances.
 *
 * @api To be used in Fusion, in php this should be implemented directly
 */
final class RemoveOperation implements OperationInterface
{
    use CreateNodeHashTrait;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'remove';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    /** @param array<int, mixed> $context */
    public function canEvaluate($context): bool
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    /** @param array<int, mixed> $arguments */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        $nodeHashesToRemove = [];
        if (isset($arguments[0])) {
            if (is_iterable($arguments[0])) {
                /** @var Node $node */
                foreach ($arguments[0] as $node) {
                    $nodeHashesToRemove[] = $this->createNodeHash($node);
                }
            } elseif ($arguments[0] instanceof Node) {
                $nodeHashesToRemove[] = $this->createNodeHash($arguments[0]);
            }
        }

        $filteredContext = [];
        foreach ($flowQuery->getContext() as $node) {
            $hash = $this->createNodeHash($node);
            if (!in_array($hash, $nodeHashesToRemove, true)) {
                $filteredContext[] = $node;
            }
        }

        $flowQuery->setContext($filteredContext);
    }

    public static function getShortName(): string
    {
        return 'remove';
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
