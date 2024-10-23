<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;

/**
 * @internal implementation details of command handlers
 */
trait NodeTypeChangeInternals
{
    use ConstraintChecks;

    /**
     * NOTE: when changing this method, {@see NodeTypeChange::requireConstraintsImposedByHappyPathStrategyAreMet}
     * needs to be modified as well (as they are structurally the same)
     */
    private function deleteDisallowedNodesWhenChangingNodeType(
        ContentGraphInterface $contentGraph,
        NodeAggregate $nodeAggregate,
        NodeType $newNodeType,
        NodeAggregateIds &$alreadyRemovedNodeAggregateIds,
    ): Events {
        $events = [];
        // if we have children, we need to check whether they are still allowed
        // after we changed the node type of the $nodeAggregate to $newNodeType.
        $childNodeAggregates = $contentGraph->findChildNodeAggregates(
            $nodeAggregate->nodeAggregateId
        );
        foreach ($childNodeAggregates as $childNodeAggregate) {
            /* @var $childNodeAggregate NodeAggregate */
            // the "parent" of the $childNode is $node; so we use $newNodeType
            // (the target node type of $node after the operation) here.
            if (
                !$childNodeAggregate->classification->isTethered()
                && !$this->areNodeTypeConstraintsImposedByParentValid(
                    $newNodeType,
                    $this->requireNodeType($childNodeAggregate->nodeTypeName)
                )
                // descendants might be disallowed by both parent and grandparent after NodeTypeChange, but must be deleted only once
                && !$alreadyRemovedNodeAggregateIds->contain($childNodeAggregate->nodeAggregateId)
            ) {
                // this aggregate (or parts thereof) are DISALLOWED according to constraints.
                // We now need to find out which edges we need to remove,
                $dimensionSpacePointsToBeRemoved = $this->findDimensionSpacePointsConnectingParentAndChildAggregate(
                    $contentGraph,
                    $nodeAggregate,
                    $childNodeAggregate
                );
                // AND REMOVE THEM
                $events[] = $this->removeNodeInDimensionSpacePointSet(
                    $contentGraph,
                    $childNodeAggregate,
                    $dimensionSpacePointsToBeRemoved,
                );
                $alreadyRemovedNodeAggregateIds = $alreadyRemovedNodeAggregateIds->merge(
                    NodeAggregateIds::create($childNodeAggregate->nodeAggregateId)
                );
            }

            // we do not need to test for grandparents here, as we did not modify the grandparents.
            // Thus, if it was allowed before, it is allowed now.
            // additionally, we need to look one level down to the grandchildren as well
            // - as it could happen that these are affected by our constraint checks as well.
            $grandchildNodeAggregates = $contentGraph->findChildNodeAggregates($childNodeAggregate->nodeAggregateId);
            foreach ($grandchildNodeAggregates as $grandchildNodeAggregate) {
                // we do not need to test for the parent of grandchild (=child),
                // as we do not change the child's node type.
                // we however need to check for the grandparent node type.
                if (
                    $childNodeAggregate->nodeName !== null
                    && !$this->areNodeTypeConstraintsImposedByGrandparentValid(
                        $newNodeType, // the grandparent node type changes
                        $childNodeAggregate->nodeName,
                        $this->requireNodeType($grandchildNodeAggregate->nodeTypeName)
                    )
                    // descendants might be disallowed by both parent and grandparent after NodeTypeChange, but must be deleted only once
                    && !$alreadyRemovedNodeAggregateIds->contain($grandchildNodeAggregate->nodeAggregateId)
                ) {
                    // this aggregate (or parts thereof) are DISALLOWED according to constraints.
                    // We now need to find out which edges we need to remove,
                    $dimensionSpacePointsToBeRemoved = $this->findDimensionSpacePointsConnectingParentAndChildAggregate(
                        $contentGraph,
                        $childNodeAggregate,
                        $grandchildNodeAggregate
                    );
                    // AND REMOVE THEM
                    $events[] = $this->removeNodeInDimensionSpacePointSet(
                        $contentGraph,
                        $grandchildNodeAggregate,
                        $dimensionSpacePointsToBeRemoved,
                    );
                    $alreadyRemovedNodeAggregateIds = $alreadyRemovedNodeAggregateIds->merge(
                        NodeAggregateIds::create($grandchildNodeAggregate->nodeAggregateId)
                    );
                }
            }
        }

        return Events::fromArray($events);
    }

    private function deleteObsoleteTetheredNodesWhenChangingNodeType(
        ContentGraphInterface $contentGraph,
        NodeAggregate $nodeAggregate,
        NodeType $newNodeType,
        NodeAggregateIds &$alreadyRemovedNodeAggregateIds,
    ): Events {
        $events = [];
        // find disallowed tethered nodes
        $tetheredNodeAggregates = $contentGraph->findTetheredChildNodeAggregates($nodeAggregate->nodeAggregateId);

        foreach ($tetheredNodeAggregates as $tetheredNodeAggregate) {
            /* @var $tetheredNodeAggregate NodeAggregate */
            if (
                $tetheredNodeAggregate->nodeName !== null
                && !$newNodeType->tetheredNodeTypeDefinitions->contain($tetheredNodeAggregate->nodeName)
                && !$alreadyRemovedNodeAggregateIds->contain($tetheredNodeAggregate->nodeAggregateId)
            ) {
                // this aggregate (or parts thereof) are DISALLOWED according to constraints.
                // We now need to find out which edges we need to remove,
                $dimensionSpacePointsToBeRemoved = $this->findDimensionSpacePointsConnectingParentAndChildAggregate(
                    $contentGraph,
                    $nodeAggregate,
                    $tetheredNodeAggregate
                );
                // AND REMOVE THEM
                $events[] = $this->removeNodeInDimensionSpacePointSet(
                    $contentGraph,
                    $tetheredNodeAggregate,
                    $dimensionSpacePointsToBeRemoved,
                );
                $alreadyRemovedNodeAggregateIds = $alreadyRemovedNodeAggregateIds->merge(
                    NodeAggregateIds::create($tetheredNodeAggregate->nodeAggregateId)
                );
            }
        }

        return Events::fromArray($events);
    }

    /**
     * Find all dimension space points which connect two Node Aggregates.
     *
     * After we found wrong node type constraints between two aggregates, we need to remove exactly the edges where the
     * aggregates are connected as parent and child.
     *
     * Example: In this case, we want to find exactly the bold edge between PAR1 and A.
     *
     *          ╔══════╗ <------ $parentNodeAggregate (PAR1)
     * ┌──────┐ ║ PAR1 ║   ┌──────┐
     * │ PAR3 │ ╚══════╝   │ PAR2 │
     * └──────┘    ║       └──────┘
     *        ╲    ║          ╱
     *         ╲   ║         ╱
     *          ▼──▼──┐ ┌───▼─┐
     *          │  A  │ │  A' │ <------ $childNodeAggregate (A+A')
     *          └─────┘ └─────┘
     *
     * How do we do this?
     * - we iterate over each covered dimension space point of the full aggregate
     * - in each dimension space point, we check whether the parent node is "our" $nodeAggregate (where
     *   we originated from)
     */
    private function findDimensionSpacePointsConnectingParentAndChildAggregate(
        ContentGraphInterface $contentGraph,
        NodeAggregate $parentNodeAggregate,
        NodeAggregate $childNodeAggregate
    ): DimensionSpacePointSet {
        $points = [];
        foreach ($childNodeAggregate->coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $parentNode = $contentGraph->getSubgraph($coveredDimensionSpacePoint, VisibilityConstraints::withoutRestrictions())->findParentNode(
                $childNodeAggregate->nodeAggregateId
            );
            if (
                $parentNode
                && $parentNode->aggregateId->equals($parentNodeAggregate->nodeAggregateId)
            ) {
                $points[] = $coveredDimensionSpacePoint;
            }
        }

        return new DimensionSpacePointSet($points);
    }

    private function removeNodeInDimensionSpacePointSet(
        ContentGraphInterface $contentGraph,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $coveredDimensionSpacePointsToBeRemoved,
    ): NodeAggregateWasRemoved {
        return new NodeAggregateWasRemoved(
            $contentGraph->getWorkspaceName(),
            $contentGraph->getContentStreamId(),
            $nodeAggregate->nodeAggregateId,
            // TODO: we also use the covered dimension space points as OCCUPIED dimension space points
            // - however the OCCUPIED dimension space points are not really used by now
            // (except for the change projector, which needs love anyways...)
            OriginDimensionSpacePointSet::fromDimensionSpacePointSet(
                $coveredDimensionSpacePointsToBeRemoved
            ),
            $coveredDimensionSpacePointsToBeRemoved,
        );
    }
}
