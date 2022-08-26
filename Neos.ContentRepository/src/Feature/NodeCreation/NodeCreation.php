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

namespace Neos\ContentRepository\Feature\NodeCreation;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\Common\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;

/** @codingStandardsIgnoreStart */
use Neos\ContentRepository\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
/** @codingStandardsIgnoreEnd */
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\Common\Exception\PropertyCannotBeSet;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Feature\Common\NodeAggregateIdentifiersByNodePaths;
use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValue;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\Infrastructure\Property\PropertyType;
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
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $command->nodeTypeName,
            $command->originDimensionSpacePoint,
            $command->initiatingUserIdentifier,
            $command->parentNodeAggregateIdentifier,
            $command->succeedingSiblingNodeAggregateIdentifier,
            $command->nodeName,
            $this->getPropertyConverter()->serializePropertyValues(
                $command->initialPropertyValues,
                $this->requireNodeType($command->nodeTypeName)
            ),
            $command->tetheredDescendantNodeAggregateIdentifiers
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
        foreach ($propertyValues->getValues() as $propertyName => $propertyValue) {
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
                    $propertyType->getValue()
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
        $this->requireContentStreamToExist($command->contentStreamIdentifier, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->originDimensionSpacePoint->toDimensionSpacePoint());
        $nodeType = $this->requireNodeType($command->nodeTypeName);
        $this->requireNodeTypeToNotBeAbstract($nodeType);
        $this->requireNodeTypeToNotBeOfTypeRoot($nodeType);
        $this->requireTetheredDescendantNodeTypesToExist($nodeType);
        $this->requireTetheredDescendantNodeTypesToNotBeOfTypeRoot($nodeType);
        if ($this->areAncestorNodeTypeConstraintChecksEnabled()) {
            $this->requireConstraintsImposedByAncestorsAreMet(
                $command->contentStreamIdentifier,
                $nodeType,
                $command->nodeName,
                [$command->parentNodeAggregateIdentifier],
                $contentRepository
            );
        }
        $this->requireProjectedNodeAggregateToNotExist(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $contentRepository
        );
        $parentNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->parentNodeAggregateIdentifier,
            $contentRepository
        );
        if ($command->succeedingSiblingNodeAggregateIdentifier) {
            $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $command->succeedingSiblingNodeAggregateIdentifier,
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
            $parentNodeAggregate->getCoveredDimensionSpacePoints()
        );
        if ($command->nodeName) {
            $this->requireNodeNameToBeUnoccupied(
                $command->contentStreamIdentifier,
                $command->nodeName,
                $command->parentNodeAggregateIdentifier,
                $command->originDimensionSpacePoint,
                $coveredDimensionSpacePoints,
                $contentRepository
            );
        }
        $descendantNodeAggregateIdentifiers = self::populateNodeAggregateIdentifiers(
            $nodeType,
            $command->tetheredDescendantNodeAggregateIdentifiers
        );
        // Write the auto-created descendant node aggregate identifiers back to the command;
        // so that when rebasing the command, it stays fully deterministic.
        $command = $command->withTetheredDescendantNodeAggregateIdentifiers($descendantNodeAggregateIdentifiers);

        foreach (
            $descendantNodeAggregateIdentifiers->getNodeAggregateIdentifiers() as $descendantNodeAggregateIdentifier
        ) {
            $this->requireProjectedNodeAggregateToNotExist(
                $command->contentStreamIdentifier,
                $descendantNodeAggregateIdentifier,
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
            $command->nodeAggregateIdentifier,
            $descendantNodeAggregateIdentifiers,
            null,
            $contentRepository
        )));

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
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
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $command->nodeTypeName,
            $command->originDimensionSpacePoint,
            $coveredDimensionSpacePoints,
            $command->parentNodeAggregateIdentifier,
            $command->nodeName,
            $initialPropertyValues,
            NodeAggregateClassification::CLASSIFICATION_REGULAR,
            $command->initiatingUserIdentifier,
            $command->succeedingSiblingNodeAggregateIdentifier
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
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers,
        ?NodePath $nodePath,
        ContentRepository $contentRepository,
    ): Events {
        $events = [];
        foreach ($nodeType->getAutoCreatedChildNodes() as $rawNodeName => $childNodeType) {
            $nodeName = NodeName::fromString($rawNodeName);
            $childNodePath = $nodePath
                ? $nodePath->appendPathSegment($nodeName)
                : NodePath::fromString((string) $nodeName);
            $childNodeAggregateIdentifier = $nodeAggregateIdentifiers->getNodeAggregateIdentifier($childNodePath)
                ?? NodeAggregateIdentifier::create();
            $initialPropertyValues = SerializedPropertyValues::defaultFromNodeType($childNodeType);

            $this->requireContentStreamToExist($command->contentStreamIdentifier, $contentRepository);
            $events[] = $this->createTetheredWithNode(
                $command,
                $childNodeAggregateIdentifier,
                NodeTypeName::fromString($childNodeType->getName()),
                $coveredDimensionSpacePoints,
                $parentNodeAggregateIdentifier,
                $nodeName,
                $initialPropertyValues
            );

            array_push($events, ...iterator_to_array($this->handleTetheredChildNodes(
                $command,
                $childNodeType,
                $coveredDimensionSpacePoints,
                $childNodeAggregateIdentifier,
                $nodeAggregateIdentifiers,
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
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $nodeName,
        SerializedPropertyValues $initialPropertyValues,
        NodeAggregateIdentifier $precedingNodeAggregateIdentifier = null
    ): NodeAggregateWithNodeWasCreated {
        return new NodeAggregateWithNodeWasCreated(
            $command->contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $nodeTypeName,
            $command->originDimensionSpacePoint,
            $coveredDimensionSpacePoints,
            $parentNodeAggregateIdentifier,
            $nodeName,
            $initialPropertyValues,
            NodeAggregateClassification::CLASSIFICATION_TETHERED,
            $command->initiatingUserIdentifier,
            $precedingNodeAggregateIdentifier
        );
    }

    protected static function populateNodeAggregateIdentifiers(
        NodeType $nodeType,
        ?NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers,
        NodePath $childPath = null
    ): NodeAggregateIdentifiersByNodePaths {
        if ($nodeAggregateIdentifiers === null) {
            $nodeAggregateIdentifiers = NodeAggregateIdentifiersByNodePaths::createEmpty();
        }
        // TODO: handle Multiple levels of autocreated child nodes
        foreach ($nodeType->getAutoCreatedChildNodes() as $rawChildName => $childNodeType) {
            $childName = NodeName::fromString($rawChildName);
            $childPath = $childPath
                ? $childPath->appendPathSegment($childName)
                : NodePath::fromString((string) $childName);
            if (!$nodeAggregateIdentifiers->getNodeAggregateIdentifier($childPath)) {
                $nodeAggregateIdentifiers = $nodeAggregateIdentifiers->add(
                    $childPath,
                    NodeAggregateIdentifier::create()
                );
            }
        }

        return $nodeAggregateIdentifiers;
    }
}
