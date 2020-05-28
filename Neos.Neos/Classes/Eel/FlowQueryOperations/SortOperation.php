<?php
namespace Neos\Neos\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

/**
 * "sort" operation working on ContentRepository nodes.
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
     * We can only handle ContentRepository Nodes.
     *
     * @param mixed $context
     * @return boolean
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (is_array($context) === true && (current($context) instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * First argument is the node property to sort by. Works with internal arguments (_xyz) as well.
     * Second argument is the sort direction (ASC or DESC).
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation.
     * @throws \Neos\Eel\FlowQuery\FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        /** @var array|NodeInterface[] $nodes */
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

        // Check sort flags
        $sortOptions = [];
        if (isset($arguments[2]) && !empty($arguments[2])) {
            $sortOptions = str_split(strtoupper($arguments[2]));
        }

        $sortedNodes = [];
        $sortSequence = [];
        $nodesByIdentifier = [];

        // Determine the property value to sort by
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
        $sortFlags = SORT_REGULAR;
        foreach ($sortOptions as $sortOpt) {
            // see https://www.php.net/manual/en/function.sort
            // no flag - SORT_REGULAR
            // 'N' - SORT_NUMERIC
            // 'S' - SORT_STRING
            // 'L' - SORT_LOCALE_STRING
            // 'T' - SORT_NATURAL
            // 'I' - SORT_FLAG_CASE (use as last option with SORT_STRING, SORT_LOCALE_STRING or SORT_NATURAL)
            switch ($sortOpt) {
                case 'I':
                    if ($sortFlags & (SORT_STRING | SORT_LOCALE_STRING | SORT_NATURAL)) {
                        $sortFlags |= SORT_FLAG_CASE;
                    }
                    break;
                case 'N':
                    $sortFlags = SORT_NUMERIC;
                    break;
                case 'S':
                    $sortFlags = SORT_STRING;
                    break;
                case 'L':
                    $sortFlags = SORT_LOCALE_STRING;
                    break;
                case 'T':
                    $sortFlags = SORT_NATURAL;
                    break;
            }
        }
        if ($sortOrder === 'DESC') {
            arsort($sortSequence, $sortFlags);
        } elseif ($sortOrder === 'ASC') {
            asort($sortSequence, $sortFlags);
        }

        // Build the sorted context that is returned
        foreach ($sortSequence as $nodeIdentifier => $value) {
            $sortedNodes[] = $nodesByIdentifier[$nodeIdentifier];
        }

        $flowQuery->setContext($sortedNodes);
    }
}
