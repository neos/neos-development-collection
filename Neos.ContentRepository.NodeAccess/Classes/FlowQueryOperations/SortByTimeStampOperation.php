<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\FlowQueryOperations;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

/**
 * "sortByTimeStamp" operation working on ContentRepository nodes.
 * Sorts nodes by specified timestamp.
 */
class SortByTimeStampOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'sortByTimestamp';

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
        return count($context) === 0 || (is_array($context) === true && (current($context) instanceof Node));
    }

    /**
     * First argument is the timestamp to sort by like created, lastModified, originalLastModified.
     * Second argument is the sort direction (ASC or DESC).
     *
     *      sortByTimestamp("created", "ASC")
     *      sortByTimestamp("lastModified", "DESC")
     *
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation.
     * @return void
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        /** @var array|Node[] $nodes */
        $nodes = $flowQuery->getContext();

        $sortedNodes = [];
        $sortSequence = [];
        $nodesByIdentifier = [];

        // Determine the property value to sort by
        foreach ($nodes as $node) {
            $timeStamp = match($arguments[0] ?? null) {
                'created' => $node->timestamps->created->getTimestamp(),
                'lastModified' => $node->timestamps->lastModified?->getTimestamp(),
                'originalLastModified' => $node->timestamps->originalLastModified?->getTimestamp(),
                default => throw new FlowQueryException('Please provide a timestamp (created, lastModified, originalLastModified) to sort by.', 1727367726)
            };

            $sortSequence[$node->aggregateId->value] = $timeStamp;
            $nodesByIdentifier[$node->aggregateId->value] = $node;
        }

        $sortOrder = is_string($arguments[1] ?? null) ? strtoupper($arguments[1]) : null;
        if ($sortOrder === 'DESC') {
            arsort($sortSequence);
        } elseif ($sortOrder === 'ASC') {
            asort($sortSequence);
        } else {
            throw new FlowQueryException('Please provide a valid sort direction (ASC or DESC)', 1727367837);
        }

        // Build the sorted context that is returned
        foreach ($sortSequence as $nodeIdentifier => $value) {
            $sortedNodes[] = $nodesByIdentifier[$nodeIdentifier];
        }

        $flowQuery->setContext($sortedNodes);
    }
}
