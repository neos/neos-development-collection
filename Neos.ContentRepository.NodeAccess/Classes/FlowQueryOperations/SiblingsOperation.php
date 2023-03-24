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

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;

/**
 * "siblings" operation working on ContentRepository nodes. It iterates over all
 * context elements and returns all sibling nodes or only those matching
 * the filter expression specified as optional argument.
 */
class SiblingsOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'siblings';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * {@inheritdoc}
     *
     * @param array<int,mixed> $context (or array-like object)  onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context): bool
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        $output = [];
        $outputNodeAggregateIds = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            /** @var Node $contextNode */
            $outputNodeAggregateIds[(string)$contextNode->nodeAggregateId] = true;
        }

        foreach ($flowQuery->getContext() as $contextNode) {
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($contextNode);

            $parentNode = $subgraph->findParentNode($contextNode->nodeAggregateId);
            if ($parentNode === null) {
                // no parent found
                continue;
            }

            foreach (
                $subgraph->findChildNodes($parentNode->nodeAggregateId, FindChildNodesFilter::create()) as $childNode
            ) {
                if (!isset($outputNodeAggregateIds[(string)$childNode->nodeAggregateId])) {
                    $output[] = $childNode;
                    $outputNodeAggregateIds[(string)$childNode->nodeAggregateId] = true;
                }
            }
        }
        $flowQuery->setContext($output);

        if (isset($arguments[0]) && !empty($arguments[0])) {
            $flowQuery->pushOperation('filter', $arguments);
        }
    }
}
