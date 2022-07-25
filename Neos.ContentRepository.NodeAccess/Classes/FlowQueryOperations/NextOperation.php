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

use Neos\Flow\Annotations as Flow;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\ContentRepository\NodeAccess\NodeAccessor\NodeAccessorInterface;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;

/**
 * "next" operation working on ContentRepository nodes. It iterates over all
 * context elements and returns the immediately following sibling.
 * If an optional filter expression is provided, it only returns the node
 * if it matches the given expression.
 */
class NextOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'next';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * {@inheritdoc}
     *
     * @param array<int,mixed> $context (or array-like object) onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $output = [];
        $outputNodePaths = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $contextNode->getContentStreamIdentifier(),
                $contextNode->getDimensionSpacePoint(),
                $contextNode->getVisibilityConstraints()
            );

            $nextNode = $this->getNextForNode($contextNode, $nodeAccessor);
            if ($nextNode !== null && !isset($outputNodePaths[(string)$nextNode->getNodeAggregateIdentifier()])) {
                $outputNodePaths[(string)$nextNode->getNodeAggregateIdentifier()] = true;
                $output[] = $nextNode;
            }
        }
        $flowQuery->setContext($output);

        if (isset($arguments[0]) && !empty($arguments[0])) {
            $flowQuery->pushOperation('filter', $arguments);
        }
    }

    /**
     * @param NodeInterface $contextNode The node for which the preceding node should be found
     * @param NodeAccessorInterface $nodeAccessor
     * @return NodeInterface|null The following node of $contextNode or NULL
     */
    protected function getNextForNode(NodeInterface $contextNode, NodeAccessorInterface $nodeAccessor): ?NodeInterface
    {
        $parentNode = $nodeAccessor->findParentNode($contextNode);
        if ($parentNode === null) {
            return null;
        }

        return $nodeAccessor->findChildNodes($parentNode)->next($contextNode);
    }
}
