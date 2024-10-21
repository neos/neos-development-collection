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
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

/**
 * Used to access the NodeTypeName of a ContentRepository Node.
 * @deprecated with 9.0.0-beta14 please use ${node.nodeTypeName} instead
 */
class NodeTypeNameOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'nodeTypeName';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    /**
     * {@inheritdoc}
     *
     * @var boolean
     */
    protected static $final = true;

    /**
     * {@inheritdoc}
     *
     * We can only handle ContentRepository Nodes.
     *
     * @param array<int, mixed> $context $context onto which this operation should be applied (array or array-like object)
     * @return boolean
     */
    public function canEvaluate($context): bool
    {
        return (isset($context[0]) && $context[0] instanceof Node);
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     * @return mixed
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if ($arguments !== []) {
            throw new FlowQueryException(static::$shortName . '() does not require any argument.', 1715510778);
        }
        /** @var array<int,mixed> $context */
        $context = $flowQuery->getContext();
        $node = $context[0] ?? null;
        if (!$node instanceof Node) {
            return null;
        }
        return $node->nodeTypeName->value;
    }
}
