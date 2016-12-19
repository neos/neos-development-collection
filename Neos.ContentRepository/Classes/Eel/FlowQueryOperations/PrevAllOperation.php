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
 * "prevAll" operation working on ContentRepository nodes. It iterates over all
 * context elements and returns each preceding sibling or only those matching
 * the filter expression specified as optional argument
 */
class PrevAllOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'prevAll';

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
        foreach ($flowQuery->getContext() as $contextNode) {
            $prevNodes = $this->getPrevForNode($contextNode);
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

        if (isset($arguments[0]) && !empty($arguments[0])) {
            $flowQuery->pushOperation('filter', $arguments);
        }
    }

    /**
     * @param NodeInterface $contextNode The node for which the preceding node should be found
     * @return NodeInterface The preceding nodes of $contextNode or NULL
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
}
