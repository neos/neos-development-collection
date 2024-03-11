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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateDimensionsWereUpdated;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNotRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsNotOfTypeRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

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
     * @return EventsToPublish
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregateCurrentlyExists
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws NodeTypeNotFound
     * @throws NodeTypeIsNotOfTypeRoot
     */
    private function handleCreateRootNodeAggregateWithNode(
        CreateRootNodeAggregateWithNode $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $contentStreamId = $this->requireContentStream($command->workspaceName, $contentRepository);
        $this->requireProjectedNodeAggregateToNotExist(
            $contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $nodeType = $this->requireNodeType($command->nodeTypeName);
        $this->requireNodeTypeToNotBeAbstract($nodeType);
        $this->requireNodeTypeToBeOfTypeRoot($nodeType);
        $this->requireRootNodeTypeToBeUnoccupied(
            $nodeType->name,
            $contentStreamId,
            $contentRepository
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
                $contentStreamId,
                $this->getAllowedDimensionSubspace()
            )
        ];

        foreach ($this->getInterDimensionalVariationGraph()->getRootGeneralizations() as $rootGeneralization) {
            array_push($events, ...iterator_to_array($this->handleTetheredRootChildNodes(
                $contentStreamId,
                $nodeType,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($rootGeneralization),
                $this->getInterDimensionalVariationGraph()->getSpecializationSet($rootGeneralization, true),
                $command->nodeAggregateId,
                $command->tetheredDescendantNodeAggregateIds,
                null,
                $contentRepository
            )));
        }

        $contentStreamEventStream = ContentStreamEventStreamName::fromContentStreamId($contentStreamId);
        return new EventsToPublish(
            $contentStreamEventStream->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                Events::fromArray($events)
            ),
            ExpectedVersion::ANY()
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
        ContentRepository $contentRepository
    ): EventsToPublish {
        $contentStreamId = $this->requireContentStream($command->workspaceName, $contentRepository);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        if (!$nodeAggregate->classification->isRoot()) {
            throw new NodeAggregateIsNotRoot('The node aggregate ' . $nodeAggregate->nodeAggregateId->value . ' is not classified as root, but should be for command UpdateRootNodeAggregateDimensions.', 1678647355);
        }

        $events = Events::with(
            new RootNodeAggregateDimensionsWereUpdated(
                $contentStreamId,
                $command->nodeAggregateId,
                $this->getAllowedDimensionSubspace()
            )
        );

        $contentStreamEventStream = ContentStreamEventStreamName::fromContentStreamId(
            $contentStreamId
        );
        return new EventsToPublish(
            $contentStreamEventStream->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            ExpectedVersion::ANY()
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
        ?NodePath $nodePath,
        ContentRepository $contentRepository,
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
                $childNodePath,
                $contentRepository
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
        NodeAggregateId $precedingNodeAggregateId = null
    ): NodeAggregateWithNodeWasCreated {
        return new NodeAggregateWithNodeWasCreated(
            $contentStreamId,
            $nodeAggregateId,
            $nodeTypeName,
            $originDimensionSpacePoint,
            $coveredDimensionSpacePoints,
            $parentNodeAggregateId,
            $nodeName,
            $initialPropertyValues,
            NodeAggregateClassification::CLASSIFICATION_TETHERED,
            $precedingNodeAggregateId
        );
    }
}
