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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Dto\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\NodeType\NodeType;
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
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeTypeChange
{
    abstract protected function requireProjectedNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        ContentRepository $contentRepository
    ): NodeAggregate;

    abstract protected function requireConstraintsImposedByAncestorsAreMet(
        ContentStreamId $contentStreamId,
        NodeType $nodeType,
        ?NodeName $nodeName,
        array $parentNodeAggregateIds,
        ContentRepository $contentRepository
    ): void;

    abstract protected function requireNodeTypeConstraintsImposedByParentToBeMet(
        NodeType $parentsNodeType,
        ?NodeName $nodeName,
        NodeType $nodeType
    ): void;

    abstract protected function areNodeTypeConstraintsImposedByParentValid(
        NodeType $parentsNodeType,
        ?NodeName $nodeName,
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

    abstract protected static function populateNodeAggregateIds(
        NodeType $nodeType,
        NodeAggregateIdsByNodePaths $nodeAggregateIds,
        NodePath $childPath = null
    ): NodeAggregateIdsByNodePaths;

    abstract protected function createEventsForMissingTetheredNode(
        NodeAggregate $parentNodeAggregate,
        Node $parentNode,
        NodeName $tetheredNodeName,
        NodeAggregateId $tetheredNodeAggregateId,
        NodeType $expectedTetheredNodeType,
        ContentRepository $contentRepository
    ): Events;

    /**
     * @throws NodeTypeNotFound
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    private function handleChangeNodeAggregateType(
        ChangeNodeAggregateType $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        /**************
         * Constraint checks
         **************/
        // existence of content stream, node type and node aggregate
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $newNodeType = $this->requireNodeType($command->newNodeTypeName);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );

        // node type detail checks
        $this->requireNodeTypeToNotBeOfTypeRoot($newNodeType);
        $this->requireTetheredDescendantNodeTypesToExist($newNodeType);
        $this->requireTetheredDescendantNodeTypesToNotBeOfTypeRoot($newNodeType);

        // the new node type must be allowed at this position in the tree
        $parentNodeAggregates = $contentRepository->getContentGraph()->findParentNodeAggregates(
            $nodeAggregate->contentStreamId,
            $nodeAggregate->nodeAggregateId
        );
        foreach ($parentNodeAggregates as $parentNodeAggregate) {
            assert($parentNodeAggregate instanceof NodeAggregate);
            $this->requireConstraintsImposedByAncestorsAreMet(
                $command->contentStreamId,
                $newNodeType,
                $nodeAggregate->nodeName,
                [$parentNodeAggregate->nodeAggregateId],
                $contentRepository
            );
        }

        /** @codingStandardsIgnoreStart */
        match ($command->strategy) {
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_HAPPY_PATH
                => $this->requireConstraintsImposedByHappyPathStrategyAreMet($nodeAggregate, $newNodeType, $contentRepository),
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE => null
        };
        /** @codingStandardsIgnoreStop */

        /**************
         * Preparation - make the command fully deterministic in case of rebase
         **************/
        $descendantNodeAggregateIds = static::populateNodeAggregateIds(
            $newNodeType,
            $command->tetheredDescendantNodeAggregateIds
        );
        // Write the auto-created descendant node aggregate ids back to the command;
        // so that when rebasing the command, it stays fully deterministic.
        $command = $command->withTetheredDescendantNodeAggregateIds($descendantNodeAggregateIds);

        /**************
         * Creating the events
         **************/
        $events = [
            new NodeAggregateTypeWasChanged(
                $command->contentStreamId,
                $command->nodeAggregateId,
                $command->newNodeTypeName
            ),
        ];

        // remove disallowed nodes
        if ($command->strategy === NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE) {
            array_push($events, ...iterator_to_array($this->deleteDisallowedNodesWhenChangingNodeType(
                $nodeAggregate,
                $newNodeType,
                $contentRepository
            )));
            array_push($events, ...iterator_to_array($this->deleteObsoleteTetheredNodesWhenChangingNodeType(
                $nodeAggregate,
                $newNodeType,
                $contentRepository
            )));
        }

        // new tethered child nodes
        $expectedTetheredNodes = $newNodeType->getAutoCreatedChildNodes();
        foreach ($nodeAggregate->getNodes() as $node) {
            assert($node instanceof Node);
            foreach ($expectedTetheredNodes as $serializedTetheredNodeName => $expectedTetheredNodeType) {
                $tetheredNodeName = NodeName::fromString($serializedTetheredNodeName);

                $subgraph = $contentRepository->getContentGraph()->getSubgraph(
                    $node->subgraphIdentity->contentStreamId,
                    $node->originDimensionSpacePoint->toDimensionSpacePoint(),
                    VisibilityConstraints::withoutRestrictions()
                );
                $tetheredNode = $subgraph->findChildNodeConnectedThroughEdgeName(
                    $node->nodeAggregateId,
                    $tetheredNodeName
                );
                if ($tetheredNode === null) {
                    $tetheredNodeAggregateId = $command->tetheredDescendantNodeAggregateIds
                        ?->getNodeAggregateId(NodePath::fromString((string)$tetheredNodeName))
                        ?: NodeAggregateId::create();
                    array_push($events, ...iterator_to_array($this->createEventsForMissingTetheredNode(
                        $nodeAggregate,
                        $node,
                        $tetheredNodeName,
                        $tetheredNodeAggregateId,
                        $expectedTetheredNodeType,
                        $contentRepository
                    )));
                }
            }
        }

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId(
                $command->contentStreamId
            )->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                Events::fromArray($events),
            ),
            ExpectedVersion::ANY()
        );
    }


    /**
     * NOTE: when changing this method, {@see NodeTypeChange::deleteDisallowedNodesWhenChangingNodeType}
     * needs to be modified as well (as they are structurally the same)
     * @throws NodeConstraintException|NodeTypeNotFoundException
     */
    private function requireConstraintsImposedByHappyPathStrategyAreMet(
        NodeAggregate $nodeAggregate,
        NodeType $newNodeType,
        ContentRepository $contentRepository
    ): void {
        // if we have children, we need to check whether they are still allowed
        // after we changed the node type of the $nodeAggregate to $newNodeType.
        $childNodeAggregates = $contentRepository->getContentGraph()->findChildNodeAggregates(
            $nodeAggregate->contentStreamId,
            $nodeAggregate->nodeAggregateId
        );
        foreach ($childNodeAggregates as $childNodeAggregate) {
            /* @var $childNodeAggregate NodeAggregate */
            // the "parent" of the $childNode is $node;
            // so we use $newNodeType (the target node type of $node after the operation) here.
            $this->requireNodeTypeConstraintsImposedByParentToBeMet(
                $newNodeType,
                $childNodeAggregate->nodeName,
                $this->requireNodeType($childNodeAggregate->nodeTypeName)
            );

            // we do not need to test for grandparents here, as we did not modify the grandparents.
            // Thus, if it was allowed before, it is allowed now.

            // additionally, we need to look one level down to the grandchildren as well
            // - as it could happen that these are affected by our constraint checks as well.
            $grandchildNodeAggregates = $contentRepository->getContentGraph()->findChildNodeAggregates(
                $childNodeAggregate->contentStreamId,
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
        NodeAggregate $nodeAggregate,
        NodeType $newNodeType,
        ContentRepository $contentRepository
    ): Events {
        $events = [];
        // if we have children, we need to check whether they are still allowed
        // after we changed the node type of the $nodeAggregate to $newNodeType.
        $childNodeAggregates = $contentRepository->getContentGraph()->findChildNodeAggregates(
            $nodeAggregate->contentStreamId,
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
                    $childNodeAggregate->nodeName,
                    $this->requireNodeType($childNodeAggregate->nodeTypeName)
                )
            ) {
                // this aggregate (or parts thereof) are DISALLOWED according to constraints.
                // We now need to find out which edges we need to remove,
                $dimensionSpacePointsToBeRemoved = $this->findDimensionSpacePointsConnectingParentAndChildAggregate(
                    $nodeAggregate,
                    $childNodeAggregate,
                    $contentRepository
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
            $grandchildNodeAggregates = $contentRepository->getContentGraph()->findChildNodeAggregates(
                $childNodeAggregate->contentStreamId,
                $childNodeAggregate->nodeAggregateId
            );
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
                        $childNodeAggregate,
                        $grandchildNodeAggregate,
                        $contentRepository
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
        NodeAggregate $nodeAggregate,
        NodeType $newNodeType,
        ContentRepository $contentRepository
    ): Events {
        $expectedTetheredNodes = $newNodeType->getAutoCreatedChildNodes();

        $events = [];
        // find disallowed tethered nodes
        $tetheredNodeAggregates = $contentRepository->getContentGraph()->findTetheredChildNodeAggregates(
            $nodeAggregate->contentStreamId,
            $nodeAggregate->nodeAggregateId
        );

        foreach ($tetheredNodeAggregates as $tetheredNodeAggregate) {
            /* @var $tetheredNodeAggregate NodeAggregate */
            if (!isset($expectedTetheredNodes[(string)$tetheredNodeAggregate->nodeName])) {
                // this aggregate (or parts thereof) are DISALLOWED according to constraints.
                // We now need to find out which edges we need to remove,
                $dimensionSpacePointsToBeRemoved = $this->findDimensionSpacePointsConnectingParentAndChildAggregate(
                    $nodeAggregate,
                    $tetheredNodeAggregate,
                    $contentRepository
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
        NodeAggregate $parentNodeAggregate,
        NodeAggregate $childNodeAggregate,
        ContentRepository $contentRepository
    ): DimensionSpacePointSet {
        $points = [];
        foreach ($childNodeAggregate->coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $subgraph = $contentRepository->getContentGraph()->getSubgraph(
                $childNodeAggregate->contentStreamId,
                $coveredDimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );
            $parentNode = $subgraph->findParentNode($childNodeAggregate->nodeAggregateId);
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
