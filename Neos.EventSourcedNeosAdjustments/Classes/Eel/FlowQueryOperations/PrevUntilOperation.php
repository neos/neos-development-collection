<?php
namespace Neos\EventSourcedNeosAdjustments\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorInterface;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\Nodes;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * "prevUntil" operation working on ContentRepository nodes. It iterates over all context elements
 * and returns each preceding sibling until the matching sibling is found.
 * If an optional filter expression is provided as a second argument,
 * it only returns the nodes matching the given expression.
 */
class PrevUntilOperation extends AbstractOperation
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
    protected static $priority = 100;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
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
     * @throws \Neos\Eel\Exception
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $output = [];
        $outputNodeIdentifiers = [];
        $until = [];

        foreach ($flowQuery->getContext() as $contextNode) {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $contextNode->getContentStreamIdentifier(),
                $contextNode->getDimensionSpacePoint(),
                $contextNode->getVisibilityConstraints()
            );

            $prevNodes = $this->getPrevForNode($contextNode, $nodeAccessor);
            if (isset($arguments[0]) && !empty($arguments[0])) {
                $untilQuery = new FlowQuery($prevNodes);
                $untilQuery->pushOperation('filter', [$arguments[0]]);

                $until = $untilQuery->get();
            }

            if (isset($until[0]) && !empty($until[0])) {
                $prevNodes = $prevNodes->until($until[0]);
            }

            foreach ($prevNodes as $prevNode) {
                if ($prevNode !== null &&
                    !isset($outputNodeIdentifiers[(string)$prevNode->getNodeAggregateIdentifier()])) {
                    $outputNodeIdentifiers[(string)$prevNode->getNodeAggregateIdentifier()] = true;
                    $output[] = $prevNode;
                }
            }
        }

        $flowQuery->setContext($output);

        if (isset($arguments[1]) && !empty($arguments[1])) {
            $flowQuery->pushOperation('filter', [$arguments[1]]);
        }
    }

    /**
     * @param NodeInterface $contextNode The node for which the next nodes should be found
     * @param NodeAccessorInterface $nodeAccessor
     * @return Nodes The following nodes of $contextNode
     */
    protected function getPrevForNode(NodeInterface $contextNode, NodeAccessorInterface $nodeAccessor): Nodes
    {
        $parentNode = $nodeAccessor->findParentNode($contextNode);
        if ($parentNode === null) {
            return Nodes::empty();
        }

        return $nodeAccessor->findChildNodes($parentNode)->previousAll($contextNode);
    }
}
