<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeTypeChange;

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Dto\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\TetheredNodeTypeDefinition;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConstraintException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/** @codingStandardsIgnoreStart */
/** @codingStandardsIgnoreEnd  */

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeTypeChange
{
    abstract protected function getNodeTypeManager(): NodeTypeManager;

    abstract protected function requireNodeAggregateToBeUntethered(NodeAggregate $nodeAggregate): void;

    abstract protected function requireProjectedNodeAggregate(
        ContentGraphInterface $contentRepository,
        NodeAggregateId $nodeAggregateId
    ): NodeAggregate;

    abstract protected function requireConstraintsImposedByAncestorsAreMet(
        ContentGraphInterface $contentGraph,
        NodeType $nodeType,
        array $parentNodeAggregateIds
    ): void;

    abstract protected function requireNodeTypeConstraintsImposedByParentToBeMet(
        NodeType $parentsNodeType,
        NodeType $nodeType
    ): void;

    abstract protected function areNodeTypeConstraintsImposedByParentValid(
        NodeType $parentsNodeType,
        NodeType $nodeType
    ): bool;

    abstract protected function requireNodeTypeConstraintsImposedByGrandparentToBeMet(
        NodeType $grandParentsNodeType,
        ?NodeName $parentNodeName,
        NodeType $nodeType
    ): void;

    abstract protected function areNodeTypeConstraintsImposedByGrandparentValid(
        NodeType $grandParentsNodeType,
        ?NodeName $parentNodeName,
        NodeType $nodeType
    ): bool;

    abstract protected function createEventsForMissingTetheredNode(
        ContentGraphInterface $contentGraph,
        NodeAggregate $parentNodeAggregate,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        TetheredNodeTypeDefinition $tetheredNodeTypeDefinition,
        NodeAggregateId $tetheredNodeAggregateId
    ): Events;

    /**
     * @throws NodeTypeNotFound
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws \Exception
     */
    private function handleChangeNodeAggregateType(
        ChangeNodeAggregateType $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        /**************
         * Constraint checks
         **************/
        // existence of content stream, node type and node aggregate
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId(), $commandHandlingDependencies);
        $newNodeType = $this->requireNodeType($command->newNodeTypeName);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId
        );
        $this->requireNodeAggregateToBeUntethered($nodeAggregate);

        // node type detail checks
        $this->requireNodeTypeToNotBeOfTypeRoot($newNodeType);
        $this->requireTetheredDescendantNodeTypesToExist($newNodeType);
        $this->requireTetheredDescendantNodeTypesToNotBeOfTypeRoot($newNodeType);

        // the new node type must be allowed at this position in the tree
        $parentNodeAggregates = $contentGraph->findParentNodeAggregates(
            $nodeAggregate->nodeAggregateId
        );
        foreach ($parentNodeAggregates as $parentNodeAggregate) {
            assert($parentNodeAggregate instanceof NodeAggregate);
            $this->requireConstraintsImposedByAncestorsAreMet(
                $contentGraph,
                $newNodeType,
                [$parentNodeAggregate->nodeAggregateId]
            );
        }

        /** @codingStandardsIgnoreStart */
        match ($command->strategy) {
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_HAPPY_PATH
                => $this->requireConstraintsImposedByHappyPathStrategyAreMet($contentGraph, $nodeAggregate, $newNodeType),
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE => null
        };
        /** @codingStandardsIgnoreStop */

        /**************
         * Preparation - make the command fully deterministic in case of rebase
         **************/
        $descendantNodeAggregateIds = $command->tetheredDescendantNodeAggregateIds->completeForNodeOfType(
            $command->newNodeTypeName,
            $this->nodeTypeManager
        );
        // Write the auto-created descendant node aggregate ids back to the command;
        // so that when rebasing the command, it stays fully deterministic.
        $command = $command->withTetheredDescendantNodeAggregateIds($descendantNodeAggregateIds);

        /**************
         * Creating the events
         **************/
        $events = [
            new NodeAggregateTypeWasChanged(
                $contentGraph->getContentStreamId(),
                $command->nodeAggregateId,
                $command->newNodeTypeName
            ),
        ];

        // remove disallowed nodes
        if ($command->strategy === NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE) {
            array_push($events, ...iterator_to_array($this->deleteDisallowedNodesWhenChangingNodeType(
                $contentGraph,
                $nodeAggregate,
                $newNodeType
            )));
            array_push($events, ...iterator_to_array($this->deleteObsoleteTetheredNodesWhenChangingNodeType(
                $contentGraph,
                $nodeAggregate,
                $newNodeType
            )));
        }

        // new tethered child nodes
        foreach ($nodeAggregate->getNodes() as $node) {
            assert($node instanceof Node);
            foreach ($newNodeType->tetheredNodeTypeDefinitions as $tetheredNodeTypeDefinition) {
                $tetheredNode = $contentGraph->getSubgraph(
                    $node->originDimensionSpacePoint->toDimensionSpacePoint(),
                    VisibilityConstraints::withoutRestrictions()
                )->findNodeByPath(
                    $tetheredNodeTypeDefinition->name,
                    $node->nodeAggregateId,
                );

                if ($tetheredNode === null) {
                    $tetheredNodeAggregateId = $command->tetheredDescendantNodeAggregateIds
                        ->getNodeAggregateId(NodePath::fromNodeNames($tetheredNodeTypeDefinition->name))
                        ?: NodeAggregateId::create();
                    array_push($events, ...iterator_to_array($this->createEventsForMissingTetheredNode(
                        $contentGraph,
                        $nodeAggregate,
                        $node->originDimensionSpacePoint,
                        $tetheredNodeTypeDefinition,
                        $tetheredNodeAggregateId
                    )));
                }
            }
        }

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                Events::fromArray($events),
            ),
            $expectedVersion
        );
    }


    /**
     * NOTE: when changing this method, {@see NodeTypeChange::deleteDisallowedNodesWhenChangingNodeType}
     * needs to be modified as well (as they are structurally the same)
     * @throws NodeConstraintException|NodeTypeNotFoundException
     */
    private function requireConstraintsImposedByHappyPathStrategyAreMet(
        ContentGraphInterface $contentGraph,
        NodeAggregate $nodeAggregate,
        NodeType $newNodeType
    ): void {
        // if we have children, we need to check whether they are still allowed
        // after we changed the node type of the $nodeAggregate to $newNodeType.
        $childNodeAggregates = $contentGraph->findChildNodeAggregates(
            $nodeAggregate->nodeAggregateId
        );
        foreach ($childNodeAggregates as $childNodeAggregate) {
            /* @var $childNodeAggregate NodeAggregate */
            // the "parent" of the $childNode is $node;
            // so we use $newNodeType (the target node type of $node after the operation) here.
            $this->requireNodeTypeConstraintsImposedByParentToBeMet(
                $newNodeType,
                $this->requireNodeType($childNodeAggregate->nodeTypeName)
            );

            // we do not need to test for grandparents here, as we did not modify the grandparents.
            // Thus, if it was allowed before, it is allowed now.
            // additionally, we need to look one level down to the grandchildren as well
            // - as it could happen that these are affected by our constraint checks as well.
            $grandchildNodeAggregates = $contentGraph->findChildNodeAggregates(
                $childNodeAggregate->nodeAggregateId
            );
            foreach ($grandchildNodeAggregates as $grandchildNodeAggregate) {
                /* @var $grandchildNodeAggregate NodeAggregate */
                // we do not need to test for the parent of grandchild (=child),
                // as we do not change the child's node type.
                // we however need to check for the grandparent node type.
                $this->requireNodeTypeConstraintsImposedByGrandparentToBeMet(
                    $newNodeType, // the grandparent node type changes
                    $childNodeAggregate->nodeName,
                    $this->requireNodeType($grandchildNodeAggregate->nodeTypeName)
                );
            }
        }
    }

    /**
     * NOTE: when changing this method, {@see NodeTypeChange::requireConstraintsImposedByHappyPathStrategyAreMet}
     * needs to be modified as well (as they are structurally the same)
     */
    private function deleteDisallowedNodesWhenChangingNodeType(
        ContentGraphInterface $contentGraph,
        NodeAggregate $nodeAggregate,
        NodeType $newNodeType
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
                    $childNodeAggregate,
                    $dimensionSpacePointsToBeRemoved,
                );
            }

            // we do not need to test for grandparents here, as we did not modify the grandparents.
            // Thus, if it was allowed before, it is allowed now.
            // additionally, we need to look one level down to the grandchildren as well
            // - as it could happen that these are affected by our constraint checks as well.
            $grandchildNodeAggregates = $contentGraph->findChildNodeAggregates($childNodeAggregate->nodeAggregateId);
            foreach ($grandchildNodeAggregates as $grandchildNodeAggregate) {
                /* @var $grandchildNodeAggregate NodeAggregate */
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
                        $grandchildNodeAggregate,
                        $dimensionSpacePointsToBeRemoved,
                    );
                }
            }
        }

        return Events::fromArray($events);
    }

    private function deleteObsoleteTetheredNodesWhenChangingNodeType(
        ContentGraphInterface $contentGraph,
        NodeAggregate $nodeAggregate,
        NodeType $newNodeType
    ): Events {
        $events = [];
        // find disallowed tethered nodes
        $tetheredNodeAggregates = $contentGraph->findTetheredChildNodeAggregates($nodeAggregate->nodeAggregateId);

        foreach ($tetheredNodeAggregates as $tetheredNodeAggregate) {
            /* @var $tetheredNodeAggregate NodeAggregate */
            if ($tetheredNodeAggregate->nodeName !== null && !$newNodeType->tetheredNodeTypeDefinitions->contain($tetheredNodeAggregate->nodeName)) {
                // this aggregate (or parts thereof) are DISALLOWED according to constraints.
                // We now need to find out which edges we need to remove,
                $dimensionSpacePointsToBeRemoved = $this->findDimensionSpacePointsConnectingParentAndChildAggregate(
                    $contentGraph,
                    $nodeAggregate,
                    $tetheredNodeAggregate
                );
                // AND REMOVE THEM
                $events[] = $this->removeNodeInDimensionSpacePointSet(
                    $tetheredNodeAggregate,
                    $dimensionSpacePointsToBeRemoved,
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
                && $parentNode->nodeAggregateId->equals($parentNodeAggregate->nodeAggregateId)
            ) {
                $points[] = $coveredDimensionSpacePoint;
            }
        }

        return new DimensionSpacePointSet($points);
    }

    private function removeNodeInDimensionSpacePointSet(
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $coveredDimensionSpacePointsToBeRemoved,
    ): NodeAggregateWasRemoved {
        return new NodeAggregateWasRemoved(
            $nodeAggregate->contentStreamId,
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
