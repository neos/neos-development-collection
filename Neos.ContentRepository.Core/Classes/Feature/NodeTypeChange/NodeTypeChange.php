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
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\Common\NodeTypeChangeInternals;
use Neos\ContentRepository\Core\Feature\Common\TetheredNodeInternals;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Dto\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\TetheredNodeTypeDefinition;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\CoverageByOrigin;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConstraintException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeTypeChange
{
    use TetheredNodeInternals;
    use NodeTypeChangeInternals;

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

    abstract protected function createEventsForMissingTetheredNodeAggregate(
        ContentGraphInterface $contentGraph,
        TetheredNodeTypeDefinition $tetheredNodeTypeDefinition,
        OriginDimensionSpacePointSet $affectedOriginDimensionSpacePoints,
        CoverageByOrigin $coverageByOrigin,
        NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        NodeAggregateIdsByNodePaths $nodeAggregateIdsByNodePaths,
        NodePath $currentNodePath,
    ): Events;

    abstract protected function createEventsForWronglyTypedNodeAggregate(
        ContentGraphInterface $contentGraph,
        NodeAggregate $nodeAggregate,
        NodeTypeName $newNodeTypeName,
        NodeAggregateIdsByNodePaths $nodeAggregateIdsByNodePaths,
        NodePath $currentNodePath,
        NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $conflictResolutionStrategy,
        NodeAggregateIds $alreadyRemovedNodeAggregates,
    ): Events;

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
     * @throws NodeTypeNotFound
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
        $this->requireContentStream($command->workspaceName, $commandHandlingDependencies);
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId(), $commandHandlingDependencies);
        $newNodeType = $this->requireNodeType($command->newNodeTypeName);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId
        );
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate);
        $this->requireNodeAggregateToBeUntethered($nodeAggregate);

        // node type detail checks
        $this->requireNodeTypeToNotBeOfTypeRoot($newNodeType);
        $this->requireNodeTypeToNotBeAbstract($newNodeType);
        $this->requireTetheredDescendantNodeTypesToExist($newNodeType);
        $this->requireTetheredDescendantNodeTypesToNotBeOfTypeRoot($newNodeType);
        $this->requireExistingDeclaredTetheredDescendantsToBeTethered($contentGraph, $nodeAggregate, $newNodeType);

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

        match ($command->strategy) {
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_HAPPY_PATH
                => $this->requireConstraintsImposedByHappyPathStrategyAreMet(
                    $contentGraph,
                    $nodeAggregate,
                    $newNodeType
                ),
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE => null
        };

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
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $command->nodeAggregateId,
                $command->newNodeTypeName,
            ),
        ];

        # Handle property adjustments
        $newNodeType = $this->requireNodeType($command->newNodeTypeName);
        foreach ($nodeAggregate->getNodes() as $node) {
            $presentPropertyKeys = array_keys(iterator_to_array($node->properties->serialized()));
            $complementaryPropertyValues = SerializedPropertyValues::defaultFromNodeType(
                $newNodeType,
                $this->propertyConverter
            )
                ->unsetProperties(PropertyNames::fromArray($presentPropertyKeys));
            $obsoletePropertyNames = PropertyNames::fromArray(
                array_diff(
                    $presentPropertyKeys,
                    array_keys($newNodeType->getProperties()),
                )
            );

            if (count($complementaryPropertyValues->values) > 0 || count($obsoletePropertyNames) > 0) {
                $events[] = new NodePropertiesWereSet(
                    $contentGraph->getWorkspaceName(),
                    $contentGraph->getContentStreamId(),
                    $nodeAggregate->nodeAggregateId,
                    $node->originDimensionSpacePoint,
                    $nodeAggregate->getCoverageByOccupant($node->originDimensionSpacePoint),
                    $complementaryPropertyValues,
                    $obsoletePropertyNames
                );
            }
        }

        // remove disallowed nodes
        $alreadyRemovedNodeAggregateIds = NodeAggregateIds::createEmpty();
        if ($command->strategy === NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE) {
            array_push($events, ...iterator_to_array($this->deleteDisallowedNodesWhenChangingNodeType(
                $contentGraph,
                $nodeAggregate,
                $newNodeType,
                $alreadyRemovedNodeAggregateIds,
            )));
            array_push($events, ...iterator_to_array($this->deleteObsoleteTetheredNodesWhenChangingNodeType(
                $contentGraph,
                $nodeAggregate,
                $newNodeType,
                $alreadyRemovedNodeAggregateIds
            )));
        }

        // handle (missing) tethered node aggregates
        $nextSibling = null;
        $succeedingSiblingIds = [];
        foreach (array_reverse(iterator_to_array($newNodeType->tetheredNodeTypeDefinitions)) as $tetheredNodeTypeDefinition) {
            $succeedingSiblingIds[$tetheredNodeTypeDefinition->name->value] = $nextSibling;
            $nextSibling = $command->tetheredDescendantNodeAggregateIds->getNodeAggregateId(NodePath::fromNodeNames($tetheredNodeTypeDefinition->name));
        }
        foreach ($newNodeType->tetheredNodeTypeDefinitions as $tetheredNodeTypeDefinition) {
            $tetheredNodeAggregate = $contentGraph->findChildNodeAggregateByName($nodeAggregate->nodeAggregateId, $tetheredNodeTypeDefinition->name);
            if ($tetheredNodeAggregate === null) {
                $events = array_merge($events, iterator_to_array($this->createEventsForMissingTetheredNodeAggregate(
                    $contentGraph,
                    $tetheredNodeTypeDefinition,
                    $nodeAggregate->occupiedDimensionSpacePoints,
                    $nodeAggregate->coverageByOccupant,
                    $nodeAggregate->nodeAggregateId,
                    $succeedingSiblingIds[$tetheredNodeTypeDefinition->nodeTypeName->value] ?? null,
                    $command->tetheredDescendantNodeAggregateIds,
                    NodePath::fromNodeNames($tetheredNodeTypeDefinition->name)
                )));
            } elseif (!$tetheredNodeAggregate->nodeTypeName->equals($tetheredNodeTypeDefinition->nodeTypeName)) {
                $events = array_merge($events, iterator_to_array($this->createEventsForWronglyTypedNodeAggregate(
                    $contentGraph,
                    $tetheredNodeAggregate,
                    $tetheredNodeTypeDefinition->nodeTypeName,
                    $command->tetheredDescendantNodeAggregateIds,
                    NodePath::fromNodeNames($tetheredNodeTypeDefinition->name),
                    $command->strategy,
                    $alreadyRemovedNodeAggregateIds
                )));
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
     * @throws NodeConstraintException|NodeTypeNotFound
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

            foreach ($newNodeType->tetheredNodeTypeDefinitions as $tetheredNodeTypeDefinition) {
                foreach ($childNodeAggregates as $childNodeAggregate) {
                    if ($childNodeAggregate->nodeName?->equals($tetheredNodeTypeDefinition->name)) {
                        $this->requireConstraintsImposedByHappyPathStrategyAreMet(
                            $contentGraph,
                            $childNodeAggregate,
                            $this->requireNodeType($tetheredNodeTypeDefinition->nodeTypeName)
                        );
                    }
                }
            }
        }
    }
}
