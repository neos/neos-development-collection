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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\SharedModel\Exception\PropertyCannotBeSet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeCreation
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function getAllowedDimensionSubspace(): DimensionSpacePointSet;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType;

    abstract protected function requireNodeTypeToNotBeAbstract(NodeType $nodeType): void;

    abstract protected function requireNodeTypeToBeOfTypeRoot(NodeType $nodeType): void;

    abstract protected function getPropertyConverter(): PropertyConverter;

    abstract protected function getNodeTypeManager(): NodeTypeManager;

    private function handleCreateNodeAggregateWithNode(
        CreateNodeAggregateWithNode $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireNodeType($command->nodeTypeName);
        $this->validateProperties(
            $this->deserializeDefaultProperties($command->nodeTypeName),
            $command->nodeTypeName
        );
        $this->validateProperties($command->initialPropertyValues, $command->nodeTypeName);

        $lowLevelCommand = CreateNodeAggregateWithNodeAndSerializedProperties::create(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $command->nodeTypeName,
            $command->originDimensionSpacePoint,
            $command->parentNodeAggregateId,
            $command->succeedingSiblingNodeAggregateId,
            $command->nodeName,
            $this->getPropertyConverter()->serializePropertyValues(
                $command->initialPropertyValues,
                $this->requireNodeType($command->nodeTypeName)
            )
        );
        if (!$command->tetheredDescendantNodeAggregateIds->isEmpty()) {
            $lowLevelCommand = $lowLevelCommand->withTetheredDescendantNodeAggregateIds($command->tetheredDescendantNodeAggregateIds);
        }

        return $this->handleCreateNodeAggregateWithNodeAndSerializedProperties($lowLevelCommand, $contentRepository);
    }

    private function deserializeDefaultProperties(NodeTypeName $nodeTypeName): PropertyValuesToWrite
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName->value);
        $defaultValues = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $defaultValue) {
            $propertyType = PropertyType::fromNodeTypeDeclaration(
                $nodeType->getPropertyType($propertyName),
                PropertyName::fromString($propertyName),
                $nodeTypeName
            );

            $defaultValues[$propertyName] = $this->getPropertyConverter()->deserializePropertyValue(
                new SerializedPropertyValue($defaultValue, $propertyType->getSerializationType())
            );
        }

        return PropertyValuesToWrite::fromArray($defaultValues);
    }

    private function validateProperties(?PropertyValuesToWrite $propertyValues, NodeTypeName $nodeTypeName): void
    {
        if (!$propertyValues) {
            return;
        }

        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName->value);
        foreach ($propertyValues->values as $propertyName => $propertyValue) {
            if (!isset($nodeType->getProperties()[$propertyName])) {
                throw PropertyCannotBeSet::becauseTheNodeTypeDoesNotDeclareIt(
                    PropertyName::fromString($propertyName),
                    $nodeTypeName
                );
            }
            $propertyType = PropertyType::fromNodeTypeDeclaration(
                $nodeType->getPropertyType($propertyName),
                PropertyName::fromString($propertyName),
                $nodeTypeName
            );
            if (!$propertyType->isMatchedBy($propertyValue)) {
                throw PropertyCannotBeSet::becauseTheValueDoesNotMatchTheConfiguredType(
                    PropertyName::fromString($propertyName),
                    is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue),
                    $propertyType->value
                );
            }
        }
    }

    /**
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeTypeNotFoundException
     */
    private function handleCreateNodeAggregateWithNodeAndSerializedProperties(
        CreateNodeAggregateWithNodeAndSerializedProperties $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->originDimensionSpacePoint->toDimensionSpacePoint());
        $nodeType = $this->requireNodeType($command->nodeTypeName);
        $this->requireNodeTypeToNotBeAbstract($nodeType);
        $this->requireNodeTypeToNotBeOfTypeRoot($nodeType);
        $this->requireTetheredDescendantNodeTypesToExist($nodeType);
        $this->requireTetheredDescendantNodeTypesToNotBeOfTypeRoot($nodeType);
        if ($this->areAncestorNodeTypeConstraintChecksEnabled()) {
            $this->requireConstraintsImposedByAncestorsAreMet(
                $command->contentStreamId,
                $nodeType,
                $command->nodeName,
                [$command->parentNodeAggregateId],
                $contentRepository
            );
        }
        $this->requireProjectedNodeAggregateToNotExist(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $parentNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->parentNodeAggregateId,
            $contentRepository
        );
        if ($command->succeedingSiblingNodeAggregateId) {
            $this->requireProjectedNodeAggregate(
                $command->contentStreamId,
                $command->succeedingSiblingNodeAggregateId,
                $contentRepository
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
            $this->requireNodeNameToBeUnoccupied(
                $command->contentStreamId,
                $command->nodeName,
                $command->parentNodeAggregateId,
                $command->originDimensionSpacePoint,
                $coveredDimensionSpacePoints,
                $contentRepository
            );
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
                $command->contentStreamId,
                $descendantNodeAggregateId,
                $contentRepository
            );
        }

        $defaultPropertyValues = SerializedPropertyValues::defaultFromNodeType($nodeType);
        $initialPropertyValues = $defaultPropertyValues->merge($command->initialPropertyValues);

        $events = [
            $this->createRegularWithNode(
                $command,
                $coveredDimensionSpacePoints,
                $initialPropertyValues
            )
        ];

        array_push($events, ...iterator_to_array($this->handleTetheredChildNodes(
            $command,
            $nodeType,
            $coveredDimensionSpacePoints,
            $command->nodeAggregateId,
            $descendantNodeAggregateIds,
            null,
            $contentRepository
        )));

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($command->contentStreamId)
                ->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand($command, Events::fromArray($events)),
            ExpectedVersion::ANY()
        );
    }

    private function createRegularWithNode(
        CreateNodeAggregateWithNodeAndSerializedProperties $command,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        SerializedPropertyValues $initialPropertyValues
    ): NodeAggregateWithNodeWasCreated {
        return new NodeAggregateWithNodeWasCreated(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $command->nodeTypeName,
            $command->originDimensionSpacePoint,
            $coveredDimensionSpacePoints,
            $command->parentNodeAggregateId,
            $command->nodeName,
            $initialPropertyValues,
            NodeAggregateClassification::CLASSIFICATION_REGULAR,
            $command->succeedingSiblingNodeAggregateId
        );
    }

    /**
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeTypeNotFoundException
     */
    private function handleTetheredChildNodes(
        CreateNodeAggregateWithNodeAndSerializedProperties $command,
        NodeType $nodeType,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateId $parentNodeAggregateId,
        NodeAggregateIdsByNodePaths $nodeAggregateIds,
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
            $childNodeAggregateId = $nodeAggregateIds->getNodeAggregateId($childNodePath)
                ?? NodeAggregateId::create();
            $initialPropertyValues = SerializedPropertyValues::defaultFromNodeType($childNodeType);

            $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
            $events[] = $this->createTetheredWithNode(
                $command,
                $childNodeAggregateId,
                $childNodeType->name,
                $coveredDimensionSpacePoints,
                $parentNodeAggregateId,
                $nodeName,
                $initialPropertyValues
            );

            array_push($events, ...iterator_to_array($this->handleTetheredChildNodes(
                $command,
                $childNodeType,
                $coveredDimensionSpacePoints,
                $childNodeAggregateId,
                $nodeAggregateIds,
                $childNodePath,
                $contentRepository
            )));
        }

        return Events::fromArray($events);
    }

    private function createTetheredWithNode(
        CreateNodeAggregateWithNodeAndSerializedProperties $command,
        NodeAggregateId $nodeAggregateId,
        NodeTypeName $nodeTypeName,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateId $parentNodeAggregateId,
        NodeName $nodeName,
        SerializedPropertyValues $initialPropertyValues,
        NodeAggregateId $precedingNodeAggregateId = null
    ): NodeAggregateWithNodeWasCreated {
        return new NodeAggregateWithNodeWasCreated(
            $command->contentStreamId,
            $nodeAggregateId,
            $nodeTypeName,
            $command->originDimensionSpacePoint,
            $coveredDimensionSpacePoints,
            $parentNodeAggregateId,
            $nodeName,
            $initialPropertyValues,
            NodeAggregateClassification::CLASSIFICATION_TETHERED,
            $precedingNodeAggregateId
        );
    }
}
