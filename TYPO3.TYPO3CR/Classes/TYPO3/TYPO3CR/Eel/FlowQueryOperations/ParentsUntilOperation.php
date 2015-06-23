<?php
namespace TYPO3\TYPO3CR\Eel\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * "parentsUntil" operation working on TYPO3CR nodes. It iterates over all
 * context elements and returns the parent nodes until the matching parent is found.
 * If an optional filter expression is provided as a second argument,
 * it only returns the nodes matching the given expression.
 */
class ParentsUntilOperation extends AbstractOperation {

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    static protected $shortName = 'parentsUntil';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    static protected $priority = 0;

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
     */
    public function canEvaluate($context) {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments) {
        $output = array();
        $outputNodePaths = array();
        foreach ($flowQuery->getContext() as $contextNode) {
            $siteNode = $contextNode->getContext()->getCurrentSiteNode();
            $parentNodes = $this->getParents($contextNode, $siteNode);

            if (isset($arguments[0]) && !empty($arguments[0])) {
                $untilQuery = new FlowQuery($parentNodes);
                $untilQuery->pushOperation('filter', array($arguments[0]));

                $until = $untilQuery->get();
            }

            if (isset($until) && !empty($until)) {
                $until = end($until);
                $parentNodes = $this->getNodesUntil($parentNodes,$until);
            }

            if (is_array($parentNodes)) {
                foreach ($parentNodes as $parentNode) {
                    if ($parentNode !== NULL && !isset($outputNodePaths[$parentNode->getPath()])) {
                        $outputNodePaths[$parentNode->getPath()] = TRUE;
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

    protected function getParents(NodeInterface $contextNode, NodeInterface $siteNode) {
        $parents = array();
        while ($contextNode !== $siteNode && $contextNode->getParent() !== NULL) {
            $contextNode = $contextNode->getParent();
            $parents[] = $contextNode;
        }
        return $parents;
    }

    /**
     * @param array $parentNodes the parent nodes
     * @param NodeInterface $until
     * @return array
     */
    protected function getNodesUntil($parentNodes, NodeInterface $until) {
        $count = count($parentNodes) - 1;

        for ($i = $count; $i >= 0; $i--) {
            if ($parentNodes[$i]->getPath() === $until->getPath()) {
                unset($parentNodes[$i]);
                return array_values($parentNodes);
            } else {
                unset($parentNodes[$i]);
            }
        }
        return array_values($parentNodes);
    }
}