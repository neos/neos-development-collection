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

        $lowLevelCommand = new CreateNodeAggregateWithNodeAndSerializedProperties(
            $command->contentStreamIdd,
            $command->nodeAggregateId,
            $command->nodeTypeName,
            $command->originDimensionSpacePoint,
            $command->parentNodeAggregateId,
            $command->succeedingSiblingNodeAggregateId,
            $command->nodeName,
            $this->getPropertyConverter()->serializePropertyValues(
                $command->initialPropertyValues,
                $this->requireNodeType($command->nodeTypeName)
            ),
            $command->tetheredDescendantNodeAggregateIds
        );

        return $this->handleCreateNodeAggregateWithNodeAndSerializedProperties($lowLevelCommand, $contentRepository);
    }

    private function deserializeDefaultProperties(NodeTypeName $nodeTypeName): PropertyValuesToWrite
    {
        $nodeType = $this->nodeTypeManager->getNodeType((string) $nodeTypeName);
        $defaultValues = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $defaultValue) {
            $propertyType = PropertyType::fromNodeTypeDeclaration(
                $nodeType->getPropertyType($propertyName),
                PropertyName::fromString($propertyName),
                $nodeTypeName
            );

            if ($defaultValue instanceof \DateTimeInterface) {
                // In NodeType::getDefaultValuesForProperties, DateTime objects are handled specially :(
                // That's why we also need to take care of them here.
                $defaultValues[$propertyName] =  $defaultValue;
            } else {
                $defaultValues[$propertyName] = $this->getPropertyConverter()->deserializePropertyValue(
                    new SerializedPropertyValue($defaultValue, $propertyType->getSerializationType())
                );
            }
        }

        return PropertyValuesToWrite::fromArray($defaultValues);
    }

    private function validateProperties(?PropertyValuesToWrite $propertyValues, NodeTypeName $nodeTypeName): void
    {
        if (!$propertyValues) {
            return;
        }

        $nodeType = $this->nodeTypeManager->getNodeType((string) $nodeTypeName);
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
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
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
        $descendantNodeAggregateIds = self::populateNodeAggregateIds(
            $nodeType,
            $command->tetheredDescendantNodeAggregateIds
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

    /**
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
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
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
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
        foreach ($nodeType->getAutoCreatedChildNodes() as $rawNodeName => $childNodeType) {
            assert($childNodeType instanceof NodeType);
            $nodeName = NodeName::fromString($rawNodeName);
            $childNodePath = $nodePath
                ? $nodePath->appendPathSegment($nodeName)
                : NodePath::fromString((string) $nodeName);
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

    /**
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
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

    protected static function populateNodeAggregateIds(
        NodeType $nodeType,
        ?NodeAggregateIdsByNodePaths $nodeAggregateIds,
        NodePath $childPath = null
    ): NodeAggregateIdsByNodePaths {
        if ($nodeAggregateIds === null) {
            $nodeAggregateIds = NodeAggregateIdsByNodePaths::createEmpty();
        }
        // TODO: handle Multiple levels of autocreated child nodes
        foreach ($nodeType->getAutoCreatedChildNodes() as $rawChildName => $childNodeType) {
            $childName = NodeName::fromString($rawChildName);
            $childPath = $childPath
                ? $childPath->appendPathSegment($childName)
                : NodePath::fromString((string) $childName);
            if (!$nodeAggregateIds->getNodeAggregateId($childPath)) {
                $nodeAggregateIds = $nodeAggregateIds->add(
                    $childPath,
                    NodeAggregateId::create()
                );
            }
        }

        return $nodeAggregateIds;
    }
}
