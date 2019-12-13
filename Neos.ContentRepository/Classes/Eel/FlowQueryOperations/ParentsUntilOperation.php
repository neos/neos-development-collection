<?php
namespace Neos\ContentRepository\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

/**
 * "parentsUntil" operation working on ContentRepository nodes. It iterates over all
 * context elements and returns the parent nodes until the matching parent is found.
 * If an optional filter expression is provided as a second argument,
 * it only returns the nodes matching the given expression.
 */
class ParentsUntilOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'parentsUntil';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 0;

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof TraversableNodeInterface));
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
        $outputNodeAggregateIdentifiers = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            $parentNodes = $this->getParents($contextNode);
            if (isset($arguments[0]) && !empty($arguments[0] && isset($parentNodes[0]))) {
                $untilQuery = new FlowQuery([$parentNodes[0]]);
                $untilQuery->pushOperation('closest', [$arguments[0]]);
                $until = $untilQuery->get();
            }

            if (isset($until) && is_array($until) && !empty($until) && isset($until[0])) {
                $parentNodes = $this->getNodesUntil($parentNodes, $until[0]);
            }

            foreach ($parentNodes as $parentNode) {
                if ($parentNode !== null && !isset($outputNodeAggregateIdentifiers[(string)$parentNode->getNodeAggregateIdentifier()])) {
                    $outputNodeAggregateIdentifiers[(string)$parentNode->getNodeAggregateIdentifier()] = true;
                    $output[] = $parentNode;
                }
            }
        }

        $flowQuery->setContext($output);

        if (isset($arguments[1]) && !empty($arguments[1])) {
            $flowQuery->pushOperation('filter', $arguments[1]);
        }
    }

    /**
     * @param TraversableNodeInterface $contextNode
     * @return TraversableNodeInterface[]
     */
    protected function getParents(TraversableNodeInterface $contextNode)
    {
        $ancestors = [];
        $node = $contextNode;
        do {
            try {
                $node = $node->findParentNode();
            } catch (NodeException $exception) {
                break;
            }
            $ancestors[] = $node;
        } while (true);
        return $ancestors;
    }

    /**
     * @param array|TraversableNodeInterface[] $parentNodes the parent nodes
     * @param TraversableNodeInterface $until
     * @return TraversableNodeInterface[]
     */
    protected function getNodesUntil(array $parentNodes, TraversableNodeInterface $until)
    {
        $count = count($parentNodes) - 1;

        for ($i = $count; $i >= 0; $i--) {
            if ($parentNodes[$i] === $until) {
                unset($parentNodes[$i]);
                return array_values($parentNodes);
            } else {
                unset($parentNodes[$i]);
            }
        }
        return array_values($parentNodes);
    }
}
