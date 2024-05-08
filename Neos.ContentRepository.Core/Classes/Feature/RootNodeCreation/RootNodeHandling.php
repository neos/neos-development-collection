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

namespace Neos\ContentRepository\Core\Feature\RootNodeCreation;

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateDimensionsWereUpdated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNotRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsNotOfTypeRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @internal implementation detail of Command Handlers
 */
trait RootNodeHandling
{
    abstract protected function getAllowedDimensionSubspace(): DimensionSpacePointSet;

    abstract protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType;

    abstract protected function requireNodeTypeToNotBeAbstract(NodeType $nodeType): void;

    abstract protected function requireNodeTypeToBeOfTypeRoot(NodeType $nodeType): void;

    /**
     * @param CreateRootNodeAggregateWithNode $command
     * @param CommandHandlingDependencies $commandHandlingDependencies
     * @return EventsToPublish
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregateCurrentlyExists
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws NodeTypeNotFound
     * @throws NodeTypeIsNotOfTypeRoot
     */
    private function handleCreateRootNodeAggregateWithNode(
        CreateRootNodeAggregateWithNode $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $this->requireContentStream($command->workspaceName, $commandHandlingDependencies);
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId(), $commandHandlingDependencies);
        $this->requireProjectedNodeAggregateToNotExist(
            $contentGraph,
            $command->nodeAggregateId
        );
        $nodeType = $this->requireNodeType($command->nodeTypeName);
        $this->requireNodeTypeToNotBeAbstract($nodeType);
        $this->requireNodeTypeToBeOfTypeRoot($nodeType);
        $this->requireRootNodeTypeToBeUnoccupied(
            $contentGraph,
            $nodeType->name
        );

        $descendantNodeAggregateIds = $command->tetheredDescendantNodeAggregateIds->completeForNodeOfType(
            $command->nodeTypeName,
            $this->nodeTypeManager
        );
        // Write the auto-created descendant node aggregate ids back to the command;
        // so that when rebasing the command, it stays fully deterministic.
        $command = $command->withTetheredDescendantNodeAggregateIds($descendantNodeAggregateIds);

        $events = [
            $this->createRootWithNode(
                $command,
                $contentGraph->getContentStreamId(),
                $this->getAllowedDimensionSubspace()
            )
        ];

        foreach ($this->getInterDimensionalVariationGraph()->getRootGeneralizations() as $rootGeneralization) {
            array_push($events, ...iterator_to_array($this->handleTetheredRootChildNodes(
                $contentGraph->getContentStreamId(),
                $nodeType,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($rootGeneralization),
                $this->getInterDimensionalVariationGraph()->getSpecializationSet($rootGeneralization, true),
                $command->nodeAggregateId,
                $command->tetheredDescendantNodeAggregateIds,
                null
            )));
        }

        $contentStreamEventStream = ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId());
        return new EventsToPublish(
            $contentStreamEventStream->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                Events::fromArray($events)
            ),
            $expectedVersion
        );
    }

    private function createRootWithNode(
        CreateRootNodeAggregateWithNode $command,
        ContentStreamId $contentStreamId,
        DimensionSpacePointSet $coveredDimensionSpacePoints
    ): RootNodeAggregateWithNodeWasCreated {
        return new RootNodeAggregateWithNodeWasCreated(
            $contentStreamId,
            $command->nodeAggregateId,
            $command->nodeTypeName,
            $coveredDimensionSpacePoints,
            NodeAggregateClassification::CLASSIFICATION_ROOT,
        );
    }

    /**
     * @param UpdateRootNodeAggregateDimensions $command
     * @return EventsToPublish
     */
    private function handleUpdateRootNodeAggregateDimensions(
        UpdateRootNodeAggregateDimensions $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId(), $commandHandlingDependencies);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId
        );
        if (!$nodeAggregate->classification->isRoot()) {
            throw new NodeAggregateIsNotRoot('The node aggregate ' . $nodeAggregate->nodeAggregateId->value . ' is not classified as root, but should be for command UpdateRootNodeAggregateDimensions.', 1678647355);
        }

        $events = Events::with(
            new RootNodeAggregateDimensionsWereUpdated(
                $contentGraph->getContentStreamId(),
                $command->nodeAggregateId,
                $this->getAllowedDimensionSubspace()
            )
        );

        $contentStreamEventStream = ContentStreamEventStreamName::fromContentStreamId(
            $contentGraph->getContentStreamId()
        );
        return new EventsToPublish(
            $contentStreamEventStream->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            $expectedVersion
        );
    }

    /**
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeTypeNotFoundException
     */
    private function handleTetheredRootChildNodes(
        ContentStreamId $contentStreamId,
        NodeType $nodeType,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateId $parentNodeAggregateId,
        NodeAggregateIdsByNodePaths $nodeAggregateIdsByNodePath,
        ?NodePath $nodePath
    ): Events {
        $events = [];
        foreach ($this->getNodeTypeManager()->getTetheredNodesConfigurationForNodeType($nodeType) as $rawNodeName => $childNodeType) {
            assert($childNodeType instanceof NodeType);
            $nodeName = NodeName::fromString($rawNodeName);
            $childNodePath = $nodePath
                ? $nodePath->appendPathSegment($nodeName)
                : NodePath::fromString($nodeName->value);
            $childNodeAggregateId = $nodeAggregateIdsByNodePath->getNodeAggregateId($childNodePath)
                ?? NodeAggregateId::create();
            $initialPropertyValues = SerializedPropertyValues::defaultFromNodeType($childNodeType, $this->getPropertyConverter());

            $events[] = $this->createTetheredWithNodeForRoot(
                $contentStreamId,
                $childNodeAggregateId,
                $childNodeType->name,
                $originDimensionSpacePoint,
                $coveredDimensionSpacePoints,
                $parentNodeAggregateId,
                $nodeName,
                $initialPropertyValues
            );

            array_push($events, ...iterator_to_array($this->handleTetheredRootChildNodes(
                $contentStreamId,
                $childNodeType,
                $originDimensionSpacePoint,
                $coveredDimensionSpacePoints,
                $childNodeAggregateId,
                $nodeAggregateIdsByNodePath,
                $childNodePath
            )));
        }

        return Events::fromArray($events);
    }

    private function createTetheredWithNodeForRoot(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        NodeTypeName $nodeTypeName,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateId $parentNodeAggregateId,
        NodeName $nodeName,
        SerializedPropertyValues $initialPropertyValues,
    ): NodeAggregateWithNodeWasCreated {
        return new NodeAggregateWithNodeWasCreated(
            $contentStreamId,
            $nodeAggregateId,
            $nodeTypeName,
            $originDimensionSpacePoint,
            InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings($coveredDimensionSpacePoints),
            $parentNodeAggregateId,
            $nodeName,
            $initialPropertyValues,
            NodeAggregateClassification::CLASSIFICATION_TETHERED,
        );
    }
}
