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

use Neos\ContentRepository\Core\NodeType\NodeTypeConstraintParser;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FizzleParser;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;

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
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * {@inheritdoc}
     *
     * @param array<int,mixed> $context (or array-like object) onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return isset($context[0]) && ($context[0] instanceof Node);
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     * @return void
     * @throws \Neos\Eel\FlowQuery\FizzleException
     * @throws \Neos\Eel\Exception
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $output = [];
        $outputNodeAggregateIds = [];
        if (isset($arguments[0]) && !empty($arguments[0])) {
            $parsedFilter = FizzleParser::parseFilterGroup($arguments[0]);
            if ($this->earlyOptimizationOfFilters($flowQuery, $parsedFilter)) {
                return;
            }
        }

        /** @var Node $contextNode */
        foreach ($flowQuery->getContext() as $contextNode) {
            $childNodes = $this->contentRepositoryRegistry->subgraphForNode($contextNode)
                ->findChildNodes($contextNode->nodeAggregateId, FindChildNodesFilter::create());
            foreach ($childNodes as $childNode) {
                if (!isset($outputNodeAggregateIds[(string)$childNode->nodeAggregateId])) {
                    $output[] = $childNode;
                    $outputNodeAggregateIds[(string)$childNode->nodeAggregateId] = true;
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
     * @param FlowQuery<int,mixed> $flowQuery
     * @param array<string,mixed> $parsedFilter
     * @return boolean
     * @throws \Neos\Eel\Exception
     */
    protected function earlyOptimizationOfFilters(FlowQuery $flowQuery, array $parsedFilter)
    {
        $optimized = false;
        $output = [];
        $outputNodeAggregateIds = [];
        foreach ($parsedFilter['Filters'] as $filter) {
            $instanceOfFilters = [];
            $attributeFilters = [];
            if (isset($filter['AttributeFilters'])) {
                foreach ($filter['AttributeFilters'] as $attributeFilter) {
                    if ($attributeFilter['Operator'] === 'instanceof' && $attributeFilter['Identifier'] === null) {
                        $instanceOfFilters[] = $attributeFilter;
                    } else {
                        $attributeFilters[] = $attributeFilter;
                    }
                }
            }

            // Only apply optimization if there's a property name filter or an instanceof filter
            // or another filter already did optimization
            if ((isset($filter['PropertyNameFilter']) || isset($filter['PathFilter']))
                || count($instanceOfFilters) > 0 || $optimized === true) {
                $optimized = true;
                $filteredOutput = [];
                $filteredOutputNodeIdentifiers = [];
                // Optimize property name filter if present
                if (isset($filter['PropertyNameFilter']) || isset($filter['PathFilter'])) {
                    $nodePath = $filter['PropertyNameFilter'] ?? $filter['PathFilter'];
                    $nodePathSegments = explode('/', $nodePath);
                    /** @var Node $contextNode */
                    foreach ($flowQuery->getContext() as $contextNode) {
                        $currentPathSegments = $nodePathSegments;
                        $resolvedNode = $contextNode;
                        while (($nodePathSegment = array_shift($currentPathSegments)) && !is_null($resolvedNode)) {
                            $resolvedNode = $this->contentRepositoryRegistry->subgraphForNode($resolvedNode)
                                ->findChildNodeConnectedThroughEdgeName(
                                $resolvedNode->nodeAggregateId,
                                NodeName::fromString($nodePathSegment)
                            );
                        }

                        if (!is_null($resolvedNode) && !isset($filteredOutputNodeIdentifiers[
                            (string)$resolvedNode->nodeAggregateId
                        ])) {
                            $filteredOutput[] = $resolvedNode;
                            $filteredOutputNodeIdentifiers[(string)$resolvedNode->nodeAggregateId] = true;
                        }
                    }
                } elseif (count($instanceOfFilters) > 0) {
                    // Optimize node type filter if present
                    $allowedNodeTypes = array_map(function ($instanceOfFilter) {
                        return $instanceOfFilter['Operand'];
                    }, $instanceOfFilters);
                    /** @var Node $contextNode */
                    foreach ($flowQuery->getContext() as $contextNode) {
                        $contentRepository = $this->contentRepositoryRegistry->get($contextNode->subgraphIdentity->contentRepositoryId);
                        $childNodes = $this->contentRepositoryRegistry->subgraphForNode($contextNode)
                            ->findChildNodes(
                            $contextNode->nodeAggregateId,
                            FindChildNodesFilter::nodeTypeConstraints(
                                NodeTypeConstraints::create(
                                    NodeTypeNames::fromStringArray($allowedNodeTypes),
                                    NodeTypeNames::createEmpty()
                                )
                            )
                        );

                        foreach ($childNodes as $childNode) {
                            if (!isset($filteredOutputNodeIdentifiers[
                                (string)$childNode->nodeAggregateId
                            ])) {
                                $filteredOutput[] = $childNode;
                                $filteredOutputNodeIdentifiers[(string)$childNode->nodeAggregateId] = true;
                            }
                        }
                    }
                }

                // Apply attribute filters if present
                if (isset($filter['AttributeFilters'])) {
                    $attributeFilters = array_reduce($filter['AttributeFilters'], function (
                        $filters,
                        $attributeFilter
                    ) {
                        return $filters . $attributeFilter['text'];
                    });
                    $filteredFlowQuery = new FlowQuery($filteredOutput);
                    $filteredFlowQuery->pushOperation('filter', [$attributeFilters]);
                    $filteredOutput = $filteredFlowQuery->getContext();
                }

                // Add filtered nodes to output
                foreach ($filteredOutput as $filteredNode) {
                    if (!isset($outputNodeAggregateIds[(string)$filteredNode->nodeAggregateId])) {
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
