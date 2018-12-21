<?php
namespace Neos\ContentRepository\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

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
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof TraversableNodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return void
     * @throws FlowQueryException
     * @throws \Neos\Eel\Exception
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (!isset($arguments[0]) || empty($arguments[0])) {
            throw new FlowQueryException('closest() requires a filter argument', 1332492263);
        }

        $output = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            $contextNodeQuery = new FlowQuery([$contextNode]);
            $contextNodeQuery->pushOperation('first', []);
            $contextNodeQuery->pushOperation('filter', $arguments);

            $parentsQuery = new FlowQuery([$contextNode]);
            $contextNodeQuery->pushOperation('add', [$parentsQuery->parents($arguments[0])->get()]);

            foreach ($contextNodeQuery as $result) {
                /* @var TraversableNodeInterface $result */
                $output[(string)$result->getNodeAggregateIdentifier()] = $result;
            }
        }

        $flowQuery->setContext(array_values($output));
    }
}
