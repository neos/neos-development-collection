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

namespace Neos\ContentRepository\Core\Feature\NodeCreation;

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\Common\NodeCreationInternals;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Exception\PropertyCannotBeSet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeCreation
{
    use NodeCreationInternals;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function getAllowedDimensionSubspace(): DimensionSpacePointSet;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType;

    abstract protected function requireNodeTypeToNotBeAbstract(NodeType $nodeType): void;

    abstract protected function requireNodeTypeToBeOfTypeRoot(NodeType $nodeType): void;

    abstract protected function requireNodeTypeNotToDeclareTetheredChildNodeName(NodeTypeName $nodeTypeName, NodeName $nodeName): void;

    abstract protected function getPropertyConverter(): PropertyConverter;

    abstract protected function getNodeTypeManager(): NodeTypeManager;

    private function handleCreateNodeAggregateWithNode(
        CreateNodeAggregateWithNode $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $this->requireNodeType($command->nodeTypeName);
        $this->validateProperties($command->initialPropertyValues, $command->nodeTypeName);

        $lowLevelCommand = CreateNodeAggregateWithNodeAndSerializedProperties::create(
            $command->workspaceName,
            $command->nodeAggregateId,
            $command->nodeTypeName,
            $command->originDimensionSpacePoint,
            $command->parentNodeAggregateId,
            $command->succeedingSiblingNodeAggregateId,
            $command->nodeName,
            $this->getPropertyConverter()->serializePropertyValues(
                $command->initialPropertyValues->withoutUnsets(),
                $this->requireNodeType($command->nodeTypeName)
            )
        );
        if (!$command->tetheredDescendantNodeAggregateIds->isEmpty()) {
            $lowLevelCommand = $lowLevelCommand->withTetheredDescendantNodeAggregateIds($command->tetheredDescendantNodeAggregateIds);
        }

        return $this->handleCreateNodeAggregateWithNodeAndSerializedProperties($lowLevelCommand, $commandHandlingDependencies);
    }

    private function validateProperties(?PropertyValuesToWrite $propertyValues, NodeTypeName $nodeTypeName): void
    {
        if (!$propertyValues) {
            return;
        }

        $nodeType = $this->requireNodeType($nodeTypeName);
        foreach ($propertyValues->values as $propertyName => $propertyValue) {
            $this->requireNodeTypeToDeclareProperty($nodeTypeName, PropertyName::fromString($propertyName));
            $propertyType = PropertyType::fromNodeTypeDeclaration(
                $nodeType->getPropertyType($propertyName),
                PropertyName::fromString($propertyName),
                $nodeTypeName
            );
            if (!$propertyType->isMatchedBy($propertyValue)) {
                throw PropertyCannotBeSet::becauseTheValueDoesNotMatchTheConfiguredType(
                    PropertyName::fromString($propertyName),
                    get_debug_type($propertyValues),
                    $propertyType->value
                );
            }
        }
    }

    /**
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeTypeNotFound
     */
    private function handleCreateNodeAggregateWithNodeAndSerializedProperties(
        CreateNodeAggregateWithNodeAndSerializedProperties $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $this->requireContentStream($command->workspaceName, $commandHandlingDependencies);
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId(), $commandHandlingDependencies);
        $this->requireDimensionSpacePointToExist($command->originDimensionSpacePoint->toDimensionSpacePoint());
        $nodeType = $this->requireNodeType($command->nodeTypeName);
        $this->requireNodeTypeToNotBeAbstract($nodeType);
        $this->requireNodeTypeToNotBeOfTypeRoot($nodeType);
        $this->requireTetheredDescendantNodeTypesToExist($nodeType);
        $this->requireTetheredDescendantNodeTypesToNotBeOfTypeRoot($nodeType);
        if ($this->areAncestorNodeTypeConstraintChecksEnabled()) {
            $this->requireConstraintsImposedByAncestorsAreMet(
                $contentGraph,
                $nodeType,
                [$command->parentNodeAggregateId]
            );
        }
        $this->requireProjectedNodeAggregateToNotExist(
            $contentGraph,
            $command->nodeAggregateId
        );
        $parentNodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->parentNodeAggregateId
        );
        if ($command->succeedingSiblingNodeAggregateId) {
            $this->requireProjectedNodeAggregate(
                $contentGraph,
                $command->succeedingSiblingNodeAggregateId
            );
        }
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $parentNodeAggregate,
            $command->originDimensionSpacePoint->toDimensionSpacePoint()
        );
        $specializations = $this->getInterDimensionalVariationGraph()->getSpecializationSet(
            $command->originDimensionSpacePoint->toDimensionSpacePoint()
        );
        $coveredDimensionSpacePoints = $specializations->getIntersection(
            $parentNodeAggregate->coveredDimensionSpacePoints
        );
        if ($command->nodeName) {
            $this->requireNodeNameToBeUncovered(
                $contentGraph,
                $command->nodeName,
                $command->parentNodeAggregateId,
            );
            $this->requireNodeTypeNotToDeclareTetheredChildNodeName($parentNodeAggregate->nodeTypeName, $command->nodeName);
        }

        $descendantNodeAggregateIds = $command->tetheredDescendantNodeAggregateIds->completeForNodeOfType(
            $command->nodeTypeName,
            $this->nodeTypeManager
        );
        // Write the auto-created descendant node aggregate ids back to the command;
        // so that when rebasing the command, it stays fully deterministic.
        $command = $command->withTetheredDescendantNodeAggregateIds($descendantNodeAggregateIds);

        foreach (
            $descendantNodeAggregateIds->getNodeAggregateIds() as $descendantNodeAggregateId
        ) {
            $this->requireProjectedNodeAggregateToNotExist(
                $contentGraph,
                $descendantNodeAggregateId
            );
        }

        $defaultPropertyValues = SerializedPropertyValues::defaultFromNodeType($nodeType, $this->getPropertyConverter());
        $initialPropertyValues = $defaultPropertyValues->merge($command->initialPropertyValues);

        $events = [
            $this->createRegularWithNode(
                $contentGraph,
                $command,
                $coveredDimensionSpacePoints,
                $initialPropertyValues
            )
        ];

        array_push($events, ...iterator_to_array($this->handleTetheredChildNodes(
            $command,
            $contentGraph,
            $nodeType,
            $coveredDimensionSpacePoints,
            $command->nodeAggregateId,
            $descendantNodeAggregateIds,
            null
        )));

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())
                ->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand($command, Events::fromArray($events)),
            $expectedVersion
        );
    }

    private function createRegularWithNode(
        ContentGraphInterface $contentGraph,
        CreateNodeAggregateWithNodeAndSerializedProperties $command,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        SerializedPropertyValues $initialPropertyValues,
    ): NodeAggregateWithNodeWasCreated {
        return new NodeAggregateWithNodeWasCreated(
            $contentGraph->getWorkspaceName(),
            $contentGraph->getContentStreamId(),
            $command->nodeAggregateId,
            $command->nodeTypeName,
            $command->originDimensionSpacePoint,
            $command->succeedingSiblingNodeAggregateId ?
                $this->resolveInterdimensionalSiblingsForCreation(
                    $contentGraph,
                    $command->succeedingSiblingNodeAggregateId,
                    $command->originDimensionSpacePoint,
                    $coveredDimensionSpacePoints
                )
                : InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings($coveredDimensionSpacePoints),
            $command->parentNodeAggregateId,
            $command->nodeName,
            $initialPropertyValues,
            NodeAggregateClassification::CLASSIFICATION_REGULAR,
        );
    }

    /**
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeTypeNotFound
     */
    private function handleTetheredChildNodes(
        CreateNodeAggregateWithNodeAndSerializedProperties $command,
        ContentGraphInterface $contentGraph,
        NodeType $nodeType,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateId $parentNodeAggregateId,
        NodeAggregateIdsByNodePaths $nodeAggregateIds,
        ?NodePath $nodePath
    ): Events {
        $events = [];
        foreach ($nodeType->tetheredNodeTypeDefinitions as $tetheredNodeTypeDefinition) {
            $childNodeType = $this->requireNodeType($tetheredNodeTypeDefinition->nodeTypeName);
            $childNodePath = $nodePath
                ? $nodePath->appendPathSegment($tetheredNodeTypeDefinition->name)
                : NodePath::fromString($tetheredNodeTypeDefinition->name->value);
            $childNodeAggregateId = $nodeAggregateIds->getNodeAggregateId($childNodePath)
                ?? NodeAggregateId::create();
            $initialPropertyValues = SerializedPropertyValues::defaultFromNodeType(
                $childNodeType,
                $this->getPropertyConverter()
            );

            $events[] = new NodeAggregateWithNodeWasCreated(
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $childNodeAggregateId,
                $tetheredNodeTypeDefinition->nodeTypeName,
                $command->originDimensionSpacePoint,
                InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings($coveredDimensionSpacePoints),
                $parentNodeAggregateId,
                $tetheredNodeTypeDefinition->name,
                $initialPropertyValues,
                NodeAggregateClassification::CLASSIFICATION_TETHERED,
            );

            array_push($events, ...iterator_to_array($this->handleTetheredChildNodes(
                $command,
                $contentGraph,
                $childNodeType,
                $coveredDimensionSpacePoints,
                $childNodeAggregateId,
                $nodeAggregateIds,
                $childNodePath
            )));
        }

        return Events::fromArray($events);
    }
}
