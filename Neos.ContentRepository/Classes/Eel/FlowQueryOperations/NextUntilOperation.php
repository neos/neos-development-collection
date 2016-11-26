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

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;

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
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
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
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $output = array();
        $outputNodePaths = array();
        $until = array();

        foreach ($flowQuery->getContext() as $contextNode) {
            $nextNodes = $this->getNextForNode($contextNode);
            if (isset($arguments[0]) && !empty($arguments[0])) {
                $untilQuery = new FlowQuery($nextNodes);
                $untilQuery->pushOperation('filter', array($arguments[0]));

                $until = $untilQuery->get();
            }

            if (isset($until[0]) && !empty($until[0])) {
                $nextNodes = $this->getNodesUntil($nextNodes, $until[0]);
            }

            if (is_array($nextNodes)) {
                foreach ($nextNodes as $nextNode) {
                    if ($nextNode !== null && !isset($outputNodePaths[$nextNode->getPath()])) {
                        $outputNodePaths[$nextNode->getPath()] = true;
                        $output[] = $nextNode;
                    }
                }
            }
        }

        $flowQuery->setContext($output);

        if (isset($arguments[1]) && !empty($arguments[1])) {
            $flowQuery->pushOperation('filter', array($arguments[1]));
        }
    }

    /**
     * @param NodeInterface $contextNode The node for which the next nodes should be found
     * @return array|NULL The following nodes of $contextNode or NULL
     */
    protected function getNextForNode(NodeInterface $contextNode)
    {
        $nodesInContext = $contextNode->getParent()->getChildNodes();
        $count = count($nodesInContext);

        for ($i = 0; $i < $count; $i++) {
            if ($nodesInContext[$i] === $contextNode) {
                unset($nodesInContext[$i]);
                return array_values($nodesInContext);
            } else {
                unset($nodesInContext[$i]);
            }
        }
        return null;
    }

    /**
     * @param array $nextNodes the remaining nodes
     * @param NodeInterface $until
     * @return array
     */
    protected function getNodesUntil($nextNodes, NodeInterface $until)
    {
        $count = count($nextNodes) - 1;

        for ($i = $count; $i >= 0; $i--) {
            if ($nextNodes[$i]->getPath() === $until->getPath()) {
                unset($nextNodes[$i]);
                return array_values($nextNodes);
            } else {
                unset($nextNodes[$i]);
            }
        }
        return array_values($nextNodes);
    }
}
