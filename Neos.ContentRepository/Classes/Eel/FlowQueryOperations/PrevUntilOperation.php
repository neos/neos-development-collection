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
    protected static $shortName = 'prevUntil';

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
        $until = [];

        foreach ($flowQuery->getContext() as $contextNode) {
            $prevNodes = $this->getPrevForNode($contextNode);

            if (isset($arguments[0]) && !empty($arguments[0])) {
                $untilQuery = new FlowQuery($prevNodes);
                $untilQuery->pushOperation('filter', [$arguments[0]]);

                $until = $untilQuery->get();
            }

            if (isset($until) && !empty($until)) {
                $until = end($until);
                $prevNodes = $this->getNodesUntil($prevNodes, $until);
            }

            if (!is_array($prevNodes)) {
                continue;
            }

            foreach ($prevNodes as $prevNode) {
                if ($prevNode !== null && !isset($outputNodeAggregateIdentifiers[(string)$prevNode->getNodeAggregateIdentifier()])) {
                    $outputNodeAggregateIdentifiers[(string)$prevNode->getNodeAggregateIdentifier()] = true;
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
     * @param TraversableNodeInterface $contextNode The node for which the previous nodes should be found
     * @return array|NULL The previous nodes of $contextNode or NULL
     */
    protected function getPrevForNode(TraversableNodeInterface $contextNode)
    {
        try {
            $parentNode = $contextNode->findParentNode();
        } catch (NodeException $e) {
            return null;
        }
        return $parentNode->findChildNodes()->previousAll($contextNode)->toArray();
    }

    /**
     * @param array|TraversableNodeInterface[] $prevNodes the remaining nodes
     * @param TraversableNodeInterface $until
     * @return TraversableNodeInterface[]
     */
    protected function getNodesUntil(array $prevNodes, TraversableNodeInterface $until)
    {
        $count = count($prevNodes);

        for ($i = 0; $i < $count; $i++) {
            if ($prevNodes[$i] === $until) {
                unset($prevNodes[$i]);
                return array_values($prevNodes);
            } else {
                unset($prevNodes[$i]);
            }
        }
        return array_values($prevNodes);
    }
}
