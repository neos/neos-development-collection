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
            $prevNodes = $this->getPrevForNode($contextNode);
            if (isset($arguments[0]) && !empty($arguments[0])) {
                $untilQuery = new FlowQuery($prevNodes);
                $untilQuery->pushOperation('filter', array($arguments[0]));

                $until = $untilQuery->get();
            }

            if (isset($until) && !empty($until)) {
                $until = end($until);
                $prevNodes = $this->getNodesUntil($prevNodes, $until);
            }

            if (is_array($prevNodes)) {
                foreach ($prevNodes as $prevNode) {
                    if ($prevNode !== null && !isset($outputNodePaths[$prevNode->getPath()])) {
                        $outputNodePaths[$prevNode->getPath()] = true;
                        $output[] = $prevNode;
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
     * @param NodeInterface $contextNode The node for which the previous nodes should be found
     * @return array|NULL The previous nodes of $contextNode or NULL
     */
    protected function getPrevForNode(NodeInterface $contextNode)
    {
        $nodesInContext = $contextNode->getParent()->getChildNodes();
        $count = count($nodesInContext) - 1;

        for ($i = $count; $i > 0; $i--) {
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
     * @param array $prevNodes the remaining nodes
     * @param NodeInterface $until
     * @return array
     */
    protected function getNodesUntil($prevNodes, NodeInterface $until)
    {
        $count = count($prevNodes);

        for ($i = 0; $i < $count; $i++) {
            if ($prevNodes[$i]->getPath() === $until->getPath()) {
                unset($prevNodes[$i]);
                return array_values($prevNodes);
            } else {
                unset($prevNodes[$i]);
            }
        }
        return array_values($prevNodes);
    }
}
