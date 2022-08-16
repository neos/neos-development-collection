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

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\ContentRepository\Projection\ContentGraph\Node;

/**
 * "closest" operation working on ContentRepository nodes. For each node in the context,
 * get the first node that matches the selector by testing the node itself and
 * traversing up through its ancestors.
 */
class ClosestOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'closest';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    /**
     * {@inheritdoc}
     *
     * @param array<int,mixed> $context (or array-like object) onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     * @return void
     * @throws FlowQueryException
     * @throws \Neos\Eel\Exception
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (empty($arguments[0])) {
            throw new FlowQueryException('closest() requires a filter argument', 1332492263);
        }

        $output = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            $contextNodeQuery = new FlowQuery([$contextNode]);
            $contextNodeQuery->pushOperation('first', []);
            $contextNodeQuery->pushOperation('filter', $arguments);

            $parentsQuery = new FlowQuery([$contextNode]);
            /** @phpstan-ignore-next-line */
            $contextNodeQuery->pushOperation('add', [$parentsQuery->parents($arguments[0])->get()]);

            foreach ($contextNodeQuery as $result) {
                /* @var Node $result */
                $output[(string)$result->nodeAggregateIdentifier] = $result;
            }
        }

        $flowQuery->setContext(array_values($output));
    }
}
