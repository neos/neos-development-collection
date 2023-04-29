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
     * Third optional argument are the sort options (see https://www.php.net/manual/en/function.sort):
     *  - 'SORT_REGULAR'
     *  - 'SORT_NUMERIC'
     *  - 'SORT_STRING'
     *  - 'SORT_LOCALE_STRING'
     *  - 'SORT_NATURAL'
     *  - 'SORT_FLAG_CASE' (use as last option with SORT_STRING, SORT_LOCALE_STRING or SORT_NATURAL)
     * A single sort option can be supplied as string. Multiple sort options are supplied as array.
     * Other than the above listed sort options throw an error. Omitting the third parameter leaves FlowQuery sort() in SORT_REGULAR sort mode.
     * Example usages:
     *      sort("title", "ASC", ["SORT_NATURAL", "SORT_FLAG_CASE"])
     *      sort("risk", "DESC", "SORT_NUMERIC")
     *
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation.
     * @throws \Neos\Eel\FlowQuery\FlowQueryException
     * @return void
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

        // Check sort options
        $sortOptions = [];
        if (isset($arguments[2]) && !empty($arguments[2])) {
            $args = is_array($arguments[2]) ? $arguments[2] : [$arguments[2]];
            foreach ($args as $arg) {
                if (!in_array(strtoupper($arg), ['SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING', 'SORT_LOCALE_STRING', 'SORT_NATURAL', 'SORT_FLAG_CASE'], true)) {
                    throw new \Neos\Eel\FlowQuery\FlowQueryException('Please provide a valid sort option (see https://www.php.net/manual/en/function.sort)', 1591107722);
                } else {
                    $sortOptions[] = $arg;
                }
            }
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
        $sortFlags = array_sum(array_map('constant', $sortOptions));
        $sortFlags = $sortFlags === 0 ? SORT_REGULAR : $sortFlags;
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
