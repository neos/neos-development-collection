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
 * "prev" operation working on ContentRepository nodes. It iterates over all
 * context elements and returns the immediately preceding sibling.
 * If an optional filter expression is provided, it only returns the node
 * if it matches the given expression.
 */
class PrevOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'prev';

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
    public function canEvaluate($context)
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
        $outputNodePaths = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            $nextNode = $this->getPrevForNode($contextNode);
            if ($nextNode !== null && !isset($outputNodePaths[(string)$nextNode->nodeAggregateId])) {
                $outputNodePaths[(string)$nextNode->nodeAggregateId] = true;
                $output[] = $nextNode;
            }
        }
        $flowQuery->setContext($output);

        if (isset($arguments[0]) && !empty($arguments[0])) {
            $flowQuery->pushOperation('filter', $arguments);
        }
    }

    /**
     * @param Node $contextNode The node for which the preceding node should be found
     * @return Node|null The preceeding node of $contextNode or NULL
     */
    protected function getPrevForNode(Node $contextNode): ?Node
    {
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($contextNode);
        $parentNode = $subgraph->findParentNode($contextNode->nodeAggregateId);
        if ($parentNode === null) {
            return null;
        }

        return $subgraph->findChildNodes($parentNode->nodeAggregateId, FindChildNodesFilter::create())
            ->previous($contextNode);
    }
}
