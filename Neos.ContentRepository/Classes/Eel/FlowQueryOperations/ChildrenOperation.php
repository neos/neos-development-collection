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

use Neos\Eel\FlowQuery\FizzleParser;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * "children" operation working on ContentRepository nodes. It iterates over all
 * context elements and returns all child nodes or only those matching
 * the filter expression specified as optional argument.
 */
class ChildrenOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'children';

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
     * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
     */
    public function canEvaluate($context)
    {
        return isset($context[0]) && ($context[0] instanceof NodeInterface);
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
        if (isset($arguments[0]) && !empty($arguments[0])) {
            $parsedFilter = FizzleParser::parseFilterGroup($arguments[0]);
            if ($this->earlyOptimizationOfFilters($flowQuery, $parsedFilter)) {
                return;
            }
        }

        /** @var NodeInterface $contextNode */
        foreach ($flowQuery->getContext() as $contextNode) {
            /** @var NodeInterface $childNode */
            foreach ($contextNode->getChildNodes() as $childNode) {
                if (!isset($outputNodePaths[$childNode->getPath()])) {
                    $output[] = $childNode;
                    $outputNodePaths[$childNode->getPath()] = true;
                }
            }
        }
        $flowQuery->setContext($output);

        if (isset($arguments[0]) && !empty($arguments[0])) {
            $flowQuery->pushOperation('filter', $arguments);
        }
    }

    /**
     * Optimize for typical use cases, filter by node name and filter
     * by NodeType (instanceof). These cases are now optimized and will
     * only load the nodes that match the filters.
     *
     * @param FlowQuery $flowQuery
     * @param array $parsedFilter
     * @return boolean
     */
    protected function earlyOptimizationOfFilters(FlowQuery $flowQuery, array $parsedFilter)
    {
        $optimized = false;
        $output = array();
        $outputNodePaths = array();
        foreach ($parsedFilter['Filters'] as $filter) {
            $instanceOfFilters = array();
            $attributeFilters = array();
            if (isset($filter['AttributeFilters'])) {
                foreach ($filter['AttributeFilters'] as $attributeFilter) {
                    if ($attributeFilter['Operator'] === 'instanceof' && $attributeFilter['Identifier'] === null) {
                        $instanceOfFilters[] = $attributeFilter;
                    } else {
                        $attributeFilters[] = $attributeFilter;
                    }
                }
            }

            // Only apply optimization if there's a property name filter or a instanceof filter or another filter already did optimization
            if ((isset($filter['PropertyNameFilter']) || isset($filter['PathFilter'])) || count($instanceOfFilters) > 0 || $optimized === true) {
                $optimized = true;
                $filteredOutput = array();
                $filteredOutputNodePaths = array();
                // Optimize property name filter if present
                if (isset($filter['PropertyNameFilter']) || isset($filter['PathFilter'])) {
                    $nodePath = isset($filter['PropertyNameFilter']) ? $filter['PropertyNameFilter'] : $filter['PathFilter'];
                    /** @var NodeInterface $contextNode */
                    foreach ($flowQuery->getContext() as $contextNode) {
                        $childNode = $contextNode->getNode($nodePath);
                        if ($childNode !== null && !isset($filteredOutputNodePaths[$childNode->getPath()])) {
                            $filteredOutput[] = $childNode;
                            $filteredOutputNodePaths[$childNode->getPath()] = true;
                        }
                    }
                } elseif (count($instanceOfFilters) > 0) {
                    // Optimize node type filter if present
                    $allowedNodeTypes = array_map(function ($instanceOfFilter) {
                        return $instanceOfFilter['Operand'];
                    }, $instanceOfFilters);
                    /** @var NodeInterface $contextNode */
                    foreach ($flowQuery->getContext() as $contextNode) {
                        /** @var NodeInterface $childNode */
                        foreach ($contextNode->getChildNodes(implode($allowedNodeTypes, ',')) as $childNode) {
                            if (!isset($filteredOutputNodePaths[$childNode->getPath()])) {
                                $filteredOutput[] = $childNode;
                                $filteredOutputNodePaths[$childNode->getPath()] = true;
                            }
                        }
                    }
                }

                // Apply attribute filters if present
                if (isset($filter['AttributeFilters'])) {
                    $attributeFilters = array_reduce($filter['AttributeFilters'], function ($filters, $attributeFilter) {
                        return $filters . $attributeFilter['text'];
                    });
                    $filteredFlowQuery = new FlowQuery($filteredOutput);
                    $filteredFlowQuery->pushOperation('filter', array($attributeFilters));
                    $filteredOutput = $filteredFlowQuery->get();
                }

                // Add filtered nodes to output
                foreach ($filteredOutput as $filteredNode) {
                    if (!isset($outputNodePaths[$filteredNode->getPath()])) {
                        $output[] = $filteredNode;
                    }
                }
            }
        }

        if ($optimized === true) {
            $flowQuery->setContext($output);
        }

        return $optimized;
    }
}
