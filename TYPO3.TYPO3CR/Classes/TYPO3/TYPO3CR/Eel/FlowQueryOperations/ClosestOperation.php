<?php
namespace TYPO3\TYPO3CR\Eel\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * "closest" operation working on TYPO3CR nodes. For each node in the context,
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
     * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (!isset($arguments[0]) || empty($arguments[0])) {
            throw new \TYPO3\Eel\FlowQuery\FlowQueryException('closest() requires a filter argument', 1332492263);
        }

        $output = array();
        foreach ($flowQuery->getContext() as $contextNode) {
            $contextNodeQuery = new FlowQuery(array($contextNode));
            $contextNodeQuery->pushOperation('first', array());
            $contextNodeQuery->pushOperation('filter', $arguments);

            $parentsQuery = new FlowQuery(array($contextNode));
            $contextNodeQuery->pushOperation('add', array($parentsQuery->parents($arguments[0])->get()));

            foreach ($contextNodeQuery as $result) {
                $output[$result->getPath()] = $result;
            }
        }

        $flowQuery->setContext(array_values($output));
    }
}
