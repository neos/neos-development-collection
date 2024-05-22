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

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;

/**
 * "nextUntil" operation working on ContentRepository nodes. It iterates over all context elements
 * and returns each following sibling until the matching sibling is found.
 * If an optional filter expression is provided as a second argument,
 * it only returns the nodes matching the given expression.
 */
class NextUntilOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'nextUntil';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 0;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

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
     * @throws \Neos\Eel\Exception
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $output = [];
        $outputNodeIdentifiers = [];

        foreach ($flowQuery->getContext() as $contextNode) {
            $nextNodes = $this->contentRepositoryRegistry->subgraphForNode($contextNode)
                ->findSucceedingSiblingNodes(
                    $contextNode->nodeAggregateId,
                    FindSucceedingSiblingNodesFilter::create()
                );
            if (isset($arguments[0]) && !empty($arguments[0])) {
                $untilQuery = new FlowQuery($nextNodes);
                $untilQuery->pushOperation('filter', [$arguments[0]]);
                $untilNodes = Nodes::fromArray(iterator_to_array($untilQuery));
            }
            if (isset($untilNodes) && !$untilNodes->isEmpty()) {
                $nextNodes = $nextNodes->previousAll($untilNodes->first());
            }
            foreach ($nextNodes as $nextNode) {
                if ($nextNode !== null
                    && !isset($outputNodeIdentifiers[$nextNode->aggregateId->value])) {
                    $outputNodeIdentifiers[$nextNode->aggregateId->value] = true;
                    $output[] = $nextNode;
                }
            }
        }

        $flowQuery->setContext($output);

        if (isset($arguments[1]) && !empty($arguments[1])) {
            $flowQuery->pushOperation('filter', [$arguments[1]]);
        }
    }
}
