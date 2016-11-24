<?php
namespace TYPO3\Neos\Eel\FlowQueryOperations;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * "sort" operation working on TYPO3CR nodes.
 * Sorts nodes by specified node properties.
 */
class SortOperation extends AbstractOperation
{

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'sort';

    /**
     * {@inheritdoc}
     *
     * We can only handle TYPO3CR Nodes.
     *
     * @param mixed $context
     * @return boolean
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * First argument is the node property to sort by. Works with internal arguments (_xyz) as well.
     * Second argument is the sort direction (ASC or DESC).
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation.
     * @return mixed
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $nodes = $flowQuery->getContext();

        // Check sort property
        if (isset($arguments[0]) && !empty($arguments[0])) {
            $sortProperty = $arguments[0];
        } else {
            throw new \Neos\Eel\FlowQuery\FlowQueryException('Please provide a node property to sort by.', 1467881104);
        }

        // Check sort direction
        if (isset($arguments[1]) && !empty($arguments[1]) && in_array(strtoupper($arguments[1]), ['ASC', 'DESC'])) {
            $sortOrder = strtoupper($arguments[1]);
        } else {
            throw new \Neos\Eel\FlowQuery\FlowQueryException('Please provide a valid sort direction (ASC or DESC)', 1467881105);
        }

        $sortedNodes = [];
        $sortSequence = [];
        $nodesByIdentifier = [];

        // Determine the property value to sort by
        /** @var Node $node */
        foreach ($nodes as $node) {
            if ($sortProperty[0] === '_') {
                $propertyValue = \Neos\Utility\ObjectAccess::getPropertyPath($node, substr($sortProperty, 1));
            } else {
                $propertyValue = $node->getProperty($sortProperty);
            }

            if ($propertyValue instanceof \DateTime) {
                $propertyValue = $propertyValue->getTimestamp();
            }

            $sortSequence[$node->getIdentifier()] = $propertyValue;
            $nodesByIdentifier[$node->getIdentifier()] = $node;
        }

        // Create the sort sequence
        if ($sortOrder === 'DESC') {
            arsort($sortSequence);
        } elseif ($sortOrder === 'ASC') {
            asort($sortSequence);
        }

        // Build the sorted context that is returned
        foreach ($sortSequence as $nodeIdentifier => $value) {
            $sortedNodes[] = $nodesByIdentifier[$nodeIdentifier];
        }

        $flowQuery->setContext($sortedNodes);
    }
}
