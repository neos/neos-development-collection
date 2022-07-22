<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\NodeTypeChange;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Feature\Common\NodeConstraintException;
use Neos\ContentRepository\Feature\Common\NodeTypeNotFoundException;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Feature\Common\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Feature\Common\NodeAggregateIdentifiersByNodePaths;
/** @codingStandardsIgnoreStart */
use Neos\ContentRepository\Feature\NodeTypeChange\Command\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
/** @codingStandardsIgnoreEnd */
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\ContentRepository\EventStore\EventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeTypeChange
{
    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getContentGraph(): ContentGraphInterface;

    abstract protected function requireProjectedNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ReadableNodeAggregateInterface;

    abstract protected function requireConstraintsImposedByAncestorsAreMet(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeType $nodeType,
        ?NodeName $nodeName,
        array $parentNodeAggregateIdentifiers
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

    abstract protected static function populateNodeAggregateIdentifiers(
        NodeType $nodeType,
        NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers,
        NodePath $childPath = null
    ): NodeAggregateIdentifiersByNodePaths;

    abstract protected function createEventsForMissingTetheredNode(
        ReadableNodeAggregateInterface $parentNodeAggregate,
        NodeInterface $parentNode,
        NodeName $tetheredNodeName,
        NodeAggregateIdentifier $tetheredNodeAggregateIdentifier,
        NodeType $expectedTetheredNodeType,
        UserIdentifier $initiatingUserIdentifier
    ): DomainEvents;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    /**
     * @throws NodeTypeNotFound
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function handleChangeNodeAggregateType(ChangeNodeAggregateType $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        /**************
         * Constraint checks
         **************/
        // existence of content stream, node type and node aggregate
        $this->requireContentStreamToExist($command->getContentStreamIdentifier());
        $newNodeType = $this->requireNodeType($command->getNewNodeTypeName());
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier()
        );

        // node type detail checks
        $this->requireNodeTypeToNotBeOfTypeRoot($newNodeType);
        $this->requireTetheredDescendantNodeTypesToExist($newNodeType);
        $this->requireTetheredDescendantNodeTypesToNotBeOfTypeRoot($newNodeType);

        // the new node type must be allowed at this position in the tree
        $parentNodeAggregates = $this->getContentGraph()->findParentNodeAggregates(
            $nodeAggregate->getContentStreamIdentifier(),
            $nodeAggregate->getIdentifier()
        );
        foreach ($parentNodeAggregates as $parentNodeAggregate) {
            $this->requireConstraintsImposedByAncestorsAreMet(
                $command->getContentStreamIdentifier(),
                $newNodeType,
                $nodeAggregate->getNodeName(),
                [$parentNodeAggregate->getIdentifier()]
            );
        }

        match ($command->getStrategy()) {
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_HAPPY_PATH
                => $this->requireConstraintsImposedByHappyPathStrategyAreMet($nodeAggregate, $newNodeType),
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE, null => null
        };

        /**************
         * Preparation - make the command fully deterministic in case of rebase
         **************/
        $descendantNodeAggregateIdentifiers = static::populateNodeAggregateIdentifiers(
            $newNodeType,
            $command->getTetheredDescendantNodeAggregateIdentifiers()
        );
        // Write the auto-created descendant node aggregate identifiers back to the command;
        // so that when rebasing the command, it stays fully deterministic.
        $command = $command->withTetheredDescendantNodeAggregateIdentifiers($descendantNodeAggregateIdentifiers);

        /**************
         * Creating the events
         **************/
        $events = DomainEvents::fromArray([]);
        $this->getNodeAggregateEventPublisher()->withCommand(
            $command,
            function () use ($command, $nodeAggregate, $newNodeType, &$events) {
                $events = DomainEvents::withSingleEvent(
                    DecoratedEvent::addIdentifier(
                        new NodeAggregateTypeWasChanged(
                            $command->getContentStreamIdentifier(),
                            $command->getNodeAggregateIdentifier(),
                            $command->getNewNodeTypeName()
                        ),
                        Uuid::uuid4()->toString()
                    )
                );

                // remove disallowed nodes
                /** @codingStandardsIgnoreStart */
                if ($command->getStrategy() === NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE) {
                /** @codingStandardsIgnoreEnd */
                    $events = $events->appendEvents($this->deleteDisallowedNodesWhenChangingNodeType(
                        $nodeAggregate,
                        $newNodeType,
                        $command->getInitiatingUserIdentifier()
                    ));
                    $events = $events->appendEvents($this->deleteObsoleteTetheredNodesWhenChangingNodeType(
                        $nodeAggregate,
                        $newNodeType,
                        $command->getInitiatingUserIdentifier()
                    ));
                }

                // new tethered child nodes
                $expectedTetheredNodes = $newNodeType->getAutoCreatedChildNodes();
                foreach ($nodeAggregate->getNodes() as $node) {
                    foreach ($expectedTetheredNodes as $serializedTetheredNodeName => $expectedTetheredNodeType) {
                        $tetheredNodeName = NodeName::fromString($serializedTetheredNodeName);

                        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
                            $node->getContentStreamIdentifier(),
                            $node->getOriginDimensionSpacePoint()->toDimensionSpacePoint(),
                            VisibilityConstraints::withoutRestrictions()
                        );
                        $tetheredNode = $subgraph->findChildNodeConnectedThroughEdgeName(
                            $node->getNodeAggregateIdentifier(),
                            $tetheredNodeName
                        );
                        if ($tetheredNode === null) {
                            $tetheredNodeAggregateIdentifier = $command->getTetheredDescendantNodeAggregateIdentifiers()
                                ?->getNodeAggregateIdentifier(NodePath::fromString((string)$tetheredNodeName))
                                ?: NodeAggregateIdentifier::create();
                            $events = $events->appendEvents($this->createEventsForMissingTetheredNode(
                                $nodeAggregate,
                                $node,
                                $tetheredNodeName,
                                $tetheredNodeAggregateIdentifier,
                                $expectedTetheredNodeType,
                                $command->getInitiatingUserIdentifier()
                            ));
                        }
                    }
                }

                $this->getNodeAggregateEventPublisher()->publishMany(
                    ContentStreamEventStreamName::fromContentStreamIdentifier(
                        $command->getContentStreamIdentifier()
                    )->getEventStreamName(),
                    $events
                );
            }
        );

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }


    /**
     * NOTE: when changing this method, {@see NodeTypeChange::deleteDisallowedNodesWhenChangingNodeType}
     * needs to be modified as well (as they are structurally the same)
     * @throws NodeConstraintException|NodeTypeNotFoundException
     */
    private function requireConstraintsImposedByHappyPathStrategyAreMet(
        ReadableNodeAggregateInterface $nodeAggregate,
        NodeType $newNodeType
    ): void {
        // if we have children, we need to check whether they are still allowed
        // after we changed the node type of the $nodeAggregate to $newNodeType.
        $childNodeAggregates = $this->getContentGraph()->findChildNodeAggregates(
            $nodeAggregate->getContentStreamIdentifier(),
            $nodeAggregate->getIdentifier()
        );
        foreach ($childNodeAggregates as $childNodeAggregate) {
            // the "parent" of the $childNode is $node;
            // so we use $newNodeType (the target node type of $node after the operation) here.
            $this->requireNodeTypeConstraintsImposedByParentToBeMet(
                $newNodeType,
                $childNodeAggregate->getNodeName(),
                $this->requireNodeType($childNodeAggregate->getNodeTypeName())
            );

            // we do not need to test for grandparents here, as we did not modify the grandparents.
            // Thus, if it was allowed before, it is allowed now.

            // additionally, we need to look one level down to the grandchildren as well
            // - as it could happen that these are affected by our constraint checks as well.
            $grandchildNodeAggregates = $this->getContentGraph()->findChildNodeAggregates(
                $childNodeAggregate->getContentStreamIdentifier(),
                $childNodeAggregate->getIdentifier()
            );
            foreach ($grandchildNodeAggregates as $grandchildNodeAggregate) {
                // we do not need to test for the parent of grandchild (=child),
                // as we do not change the child's node type.
                // we however need to check for the grandparent node type.
                $this->requireNodeTypeConstraintsImposedByGrandparentToBeMet(
                    $newNodeType, // the grandparent node type changes
                    $childNodeAggregate->getNodeName(),
                    $this->requireNodeType($grandchildNodeAggregate->getNodeTypeName())
                );
            }
        }
    }

    /**
     * NOTE: when changing this method, {@see NodeTypeChange::requireConstraintsImposedByHappyPathStrategyAreMet}
     * needs to be modified as well (as they are structurally the same)
     */
    private function deleteDisallowedNodesWhenChangingNodeType(
        ReadableNodeAggregateInterface $nodeAggregate,
        NodeType $newNodeType,
        UserIdentifier $initiatingUserIdentifier
    ): DomainEvents {
        $events = DomainEvents::createEmpty();
        // if we have children, we need to check whether they are still allowed
        // after we changed the node type of the $nodeAggregate to $newNodeType.
        $childNodeAggregates = $this->getContentGraph()->findChildNodeAggregates(
            $nodeAggregate->getContentStreamIdentifier(),
            $nodeAggregate->getIdentifier()
        );
        foreach ($childNodeAggregates as $childNodeAggregate) {
            // the "parent" of the $childNode is $node; so we use $newNodeType
            // (the target node type of $node after the operation) here.
            if (!$childNodeAggregate->isTethered() && !$this->areNodeTypeConstraintsImposedByParentValid(
                $newNodeType,
                $childNodeAggregate->getNodeName(),
                $this->requireNodeType($childNodeAggregate->getNodeTypeName())
            )) {
                // this aggregate (or parts thereof) are DISALLOWED according to constraints.
                // We now need to find out which edges we need to remove,
                $dimensionSpacePointsToBeRemoved = $this->findDimensionSpacePointsConnectingParentAndChildAggregate(
                    $nodeAggregate,
                    $childNodeAggregate
                );
                // AND REMOVE THEM
                $events = $events->appendEvent($this->removeNodeInDimensionSpacePointSet(
                    $childNodeAggregate,
                    $dimensionSpacePointsToBeRemoved,
                    $initiatingUserIdentifier
                ));
            }

            // we do not need to test for grandparents here, as we did not modify the grandparents.
            // Thus, if it was allowed before, it is allowed now.

            // additionally, we need to look one level down to the grandchildren as well
            // - as it could happen that these are affected by our constraint checks as well.
            $grandchildNodeAggregates = $this->getContentGraph()->findChildNodeAggregates(
                $childNodeAggregate->getContentStreamIdentifier(),
                $childNodeAggregate->getIdentifier()
            );
            foreach ($grandchildNodeAggregates as $grandchildNodeAggregate) {
                // we do not need to test for the parent of grandchild (=child),
                // as we do not change the child's node type.
                // we however need to check for the grandparent node type.
                if ($childNodeAggregate->getNodeName() !== null
                    && !$this->areNodeTypeConstraintsImposedByGrandparentValid(
                        $newNodeType, // the grandparent node type changes
                        $childNodeAggregate->getNodeName(),
                        $this->requireNodeType($grandchildNodeAggregate->getNodeTypeName())
                    )
                ) {
                    // this aggregate (or parts thereof) are DISALLOWED according to constraints.
                    // We now need to find out which edges we need to remove,
                    $dimensionSpacePointsToBeRemoved = $this->findDimensionSpacePointsConnectingParentAndChildAggregate(
                        $childNodeAggregate,
                        $grandchildNodeAggregate
                    );
                    // AND REMOVE THEM
                    $events = $events->appendEvent($this->removeNodeInDimensionSpacePointSet(
                        $grandchildNodeAggregate,
                        $dimensionSpacePointsToBeRemoved,
                        $initiatingUserIdentifier
                    ));
                }
            }
        }

        return $events;
    }

    private function deleteObsoleteTetheredNodesWhenChangingNodeType(
        ReadableNodeAggregateInterface $nodeAggregate,
        NodeType $newNodeType,
        UserIdentifier $initiatingUserIdentifier
    ): DomainEvents {
        $expectedTetheredNodes = $newNodeType->getAutoCreatedChildNodes();

        $events = DomainEvents::createEmpty();
        // find disallowed tethered nodes
        $tetheredNodeAggregates = $this->getContentGraph()->findTetheredChildNodeAggregates(
            $nodeAggregate->getContentStreamIdentifier(),
            $nodeAggregate->getIdentifier()
        );

        foreach ($tetheredNodeAggregates as $tetheredNodeAggregate) {
            if (!isset($expectedTetheredNodes[(string)$tetheredNodeAggregate->getNodeName()])) {
                // this aggregate (or parts thereof) are DISALLOWED according to constraints.
                // We now need to find out which edges we need to remove,
                $dimensionSpacePointsToBeRemoved = $this->findDimensionSpacePointsConnectingParentAndChildAggregate(
                    $nodeAggregate,
                    $tetheredNodeAggregate
                );
                // AND REMOVE THEM
                $events = $events->appendEvent($this->removeNodeInDimensionSpacePointSet(
                    $tetheredNodeAggregate,
                    $dimensionSpacePointsToBeRemoved,
                    $initiatingUserIdentifier
                ));
            }
        }

        return $events;
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
     * ┌──────┐ ║  PAR1║   ┌──────┐
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
        ReadableNodeAggregateInterface $parentNodeAggregate,
        ReadableNodeAggregateInterface $childNodeAggregate
    ): DimensionSpacePointSet {
        $points = [];
        foreach ($childNodeAggregate->getCoveredDimensionSpacePoints() as $coveredDimensionSpacePoint) {
            $subgraph = $this->getContentGraph()->getSubgraphByIdentifier(
                $childNodeAggregate->getContentStreamIdentifier(),
                $coveredDimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );
            $parentNode = $subgraph->findParentNode($childNodeAggregate->getIdentifier());
            if ($parentNode && $parentNode->getNodeAggregateIdentifier()
                    ->equals($parentNodeAggregate->getIdentifier())
            ) {
                $points[] = $coveredDimensionSpacePoint;
            }
        }

        return new DimensionSpacePointSet($points);
    }

    private function removeNodeInDimensionSpacePointSet(
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $coveredDimensionSpacePointsToBeRemoved,
        UserIdentifier $initiatingUserIdentifier
    ): DomainEventInterface {
        return DecoratedEvent::addIdentifier(
            new NodeAggregateWasRemoved(
                $nodeAggregate->getContentStreamIdentifier(),
                $nodeAggregate->getIdentifier(),
                // TODO: we also use the covered dimension space points as OCCUPIED dimension space points
                // - however the OCCUPIED dimension space points are not really used by now
                // (except for the change projector, which needs love anyways...)
                OriginDimensionSpacePointSet::fromDimensionSpacePointSet(
                    $coveredDimensionSpacePointsToBeRemoved
                ),
                $coveredDimensionSpacePointsToBeRemoved,
                $initiatingUserIdentifier
            ),
            Uuid::uuid4()->toString()
        );
    }
}
