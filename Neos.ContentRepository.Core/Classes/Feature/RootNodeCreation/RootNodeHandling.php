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

use Neos\ContentRepository\Core\CommandHandler\CommandHandlingDependencies;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\DimensionSpacePointsWithAllowedSpecializations;
use Neos\ContentRepository\Core\Feature\Common\DimensionSpacePointWithAllowedSpecializations;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\RebaseableCommand;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateDimensionsWereUpdated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNotRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsNotOfTypeRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

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
            array_push($events, ...$this->handleTetheredRootChildNodes(
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $nodeType,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($rootGeneralization),
                $this->getInterDimensionalVariationGraph()->getSpecializationSet($rootGeneralization, true),
                $command->nodeAggregateId,
                $command->tetheredDescendantNodeAggregateIds,
                null
            ));
        }

        $contentStreamEventStream = ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId());
        return new EventsToPublish(
            $contentStreamEventStream->getEventStreamName(),
            RebaseableCommand::enrichWithCommand(
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
            $command->workspaceName,
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
        $rootNodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId
        );
        if (!$rootNodeAggregate->classification->isRoot()) {
            throw new NodeAggregateIsNotRoot('The node aggregate ' . $rootNodeAggregate->nodeAggregateId->value . ' is not classified as root, but should be for command UpdateRootNodeAggregateDimensions.', 1678647355);
        }

        $this->requireWorkspaceToBeRootOrRootBasedForDimensionAdjustment($command->workspaceName, $commandHandlingDependencies);
        $relevantWorkspaces = $commandHandlingDependencies->findAllWorkspaces()->filter(
            fn (Workspace $workspace): bool => !$workspace->workspaceName->equals($command->initialWorkspaceName)
        );
        self::requireNoWorkspaceToHaveChanges($relevantWorkspaces);

        $allowedDimensionSubspace = $this->getAllowedDimensionSubspace();

        $newDimensionSpacePoints = $allowedDimensionSubspace->getDifference($rootNodeAggregate->coveredDimensionSpacePoints);
        foreach ($newDimensionSpacePoints as $newDimensionSpacePoint) {
            foreach ($relevantWorkspaces as $workspace) {
                self::requireDimensionSpacePointToBeEmptyInContentStream(
                    $commandHandlingDependencies->getContentGraph($workspace->workspaceName),
                    $newDimensionSpacePoint,
                );
            }
        }
        $removedDimensionSpacePoints = $rootNodeAggregate->coveredDimensionSpacePoints->getDifference($allowedDimensionSubspace);

        $generalisationsCoveredAlreadyByRootNodeAggregate = $this->getInterDimensionalVariationGraph()->getGeneralizationSetForSet($newDimensionSpacePoints, includeOrigins: false)
            ->getIntersection($rootNodeAggregate->coveredDimensionSpacePoints);

        if (!$generalisationsCoveredAlreadyByRootNodeAggregate->isEmpty()) {
            throw new \RuntimeException(sprintf('Cannot add fallback dimensions via update root node aggregate because node %s already covers generalisations %s. Use AddDimensionShineThrough instead.', $rootNodeAggregate->nodeAggregateId->value, $generalisationsCoveredAlreadyByRootNodeAggregate->toJson()), 1741898260);
        }

        $events = [];
        if (!$removedDimensionSpacePoints->isEmpty()) {
            $this->requireDescendantNodesToNotFallbackToDimensionSpacePointsOtherThan(
                $rootNodeAggregate->nodeAggregateId,
                $contentGraph,
                DimensionSpacePointsWithAllowedSpecializations::create(...array_map(
                    fn (DimensionSpacePoint $removedDimensionSpacePoint): DimensionSpacePointWithAllowedSpecializations
                        => DimensionSpacePointWithAllowedSpecializations::create(
                            $removedDimensionSpacePoint,
                            $removedDimensionSpacePoints, // only fallbacks to also removed DSPs allowed
                        ),
                    $removedDimensionSpacePoints->points
                ))
            );
            $events[] = new NodeAggregateWasRemoved(
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $rootNodeAggregate->nodeAggregateId,
                $removedDimensionSpacePoints,
            );
        }

        if (!$newDimensionSpacePoints->isEmpty()) {
            $events[] = new RootNodeAggregateDimensionsWereUpdated(
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $command->nodeAggregateId,
                $allowedDimensionSubspace
            );
        }

        if ($events === []) {
            throw new \RuntimeException(sprintf('The root node aggregate %s covers already all allowed dimensions: %s.', $rootNodeAggregate->nodeAggregateId->value, $allowedDimensionSubspace->toJson()), 1741897071);
        }

        $contentStreamEventStream = ContentStreamEventStreamName::fromContentStreamId(
            $contentGraph->getContentStreamId()
        );
        return new EventsToPublish(
            $contentStreamEventStream->getEventStreamName(),
            RebaseableCommand::enrichWithCommand(
                $command,
                Events::fromArray($events)
            ),
            $expectedVersion
        );
    }

    /**
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeTypeNotFound
     * @return array<EventInterface>
     */
    private function handleTetheredRootChildNodes(
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        NodeType $nodeType,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateId $parentNodeAggregateId,
        NodeAggregateIdsByNodePaths $nodeAggregateIdsByNodePath,
        ?NodePath $nodePath
    ): array {
        $events = [];
        foreach ($nodeType->tetheredNodeTypeDefinitions as $tetheredNodeTypeDefinition) {
            $childNodeType = $this->requireNodeType($tetheredNodeTypeDefinition->nodeTypeName);
            $childNodePath = $nodePath
                ? $nodePath->appendPathSegment($tetheredNodeTypeDefinition->name)
                : NodePath::fromNodeNames($tetheredNodeTypeDefinition->name);
            $childNodeAggregateId = $nodeAggregateIdsByNodePath->getNodeAggregateId($childNodePath)
                ?? NodeAggregateId::create();
            $initialPropertyValues = SerializedPropertyValues::defaultFromNodeType($childNodeType, $this->getPropertyConverter());

            $events[] = $this->createTetheredWithNodeForRoot(
                $workspaceName,
                $contentStreamId,
                $childNodeAggregateId,
                $tetheredNodeTypeDefinition->nodeTypeName,
                $originDimensionSpacePoint,
                $coveredDimensionSpacePoints,
                $parentNodeAggregateId,
                $tetheredNodeTypeDefinition->name,
                $initialPropertyValues
            );

            array_push($events, ...$this->handleTetheredRootChildNodes(
                $workspaceName,
                $contentStreamId,
                $childNodeType,
                $originDimensionSpacePoint,
                $coveredDimensionSpacePoints,
                $childNodeAggregateId,
                $nodeAggregateIdsByNodePath,
                $childNodePath
            ));
        }

        return $events;
    }

    abstract protected function requireDescendantNodesToNotFallbackToDimensionSpacePointsOtherThan(
        NodeAggregateId $nodeAggregateId,
        ContentGraphInterface $contentGraph,
        DimensionSpacePointsWithAllowedSpecializations $fallbackConstraints,
    ): void;

    private function createTetheredWithNodeForRoot(
        WorkspaceName $workspaceName,
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
            $workspaceName,
            $contentStreamId,
            $nodeAggregateId,
            $nodeTypeName,
            $originDimensionSpacePoint,
            InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings($coveredDimensionSpacePoints),
            $parentNodeAggregateId,
            $nodeName,
            $initialPropertyValues,
            NodeAggregateClassification::CLASSIFICATION_TETHERED,
            SerializedNodeReferences::createEmpty(),
        );
    }
}
