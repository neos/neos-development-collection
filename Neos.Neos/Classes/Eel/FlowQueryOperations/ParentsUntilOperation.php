<?php
namespace Neos\Neos\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.Neos package.
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
    protected static $priority = 100;

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
     * @todo rewrite once the node type Neos.Neos:Site is available for comparison
     * @throws \Neos\Eel\Exception
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $output = [];
        $outputNodeAggregateIdentifiers = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            /** @var NodeInterface $contextNode */
            $siteNode = $contextNode->getContext()->getCurrentSiteNode();
            $parentNodes = $this->getParents($contextNode, $siteNode);
            if (isset($arguments[0]) && !empty($arguments[0] && isset($parentNodes[0]))) {
                $untilQuery = new FlowQuery(array($parentNodes[0]));
                $untilQuery->pushOperation('closest', array($arguments[0]));
                $until = $untilQuery->get();
            }

            if (isset($until) && is_array($until) && !empty($until) && isset($until[0])) {
                $parentNodes = $this->getNodesUntil($parentNodes, $until[0]);
            }

            if (is_array($parentNodes)) {
                foreach ($parentNodes as $parentNode) {
                    if ($parentNode !== null && !isset($outputNodeAggregateIdentifiers[$parentNode->getIdentifier()])) {
                        $outputNodeAggregateIdentifiers[$parentNode->getIdentifier()] = true;
                        $output[] = $parentNode;
                    }
                }
            }
        }

        $flowQuery->setContext($output);

        if (isset($arguments[1]) && !empty($arguments[1])) {
            $flowQuery->pushOperation('filter', $arguments[1]);
        }
    }

    /**
     * @param NodeInterface $contextNode
     * @param NodeInterface $siteNode
     * @return array|NodeInterface[]
     */
    protected function getParents(NodeInterface $contextNode, NodeInterface $siteNode): array
    {
        $parents = [];
        while ($contextNode !== $siteNode && $contextNode->getParent() !== null) {
            $contextNode = $contextNode->getParent();
            $parents[] = $contextNode;
        }

        return $parents;
    }

    /**
     * @param array $parentNodes the parent nodes
     * @param NodeInterface $until
     * @return array|NodeInterface[]
     */
    protected function getNodesUntil($parentNodes, NodeInterface $until): array
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
