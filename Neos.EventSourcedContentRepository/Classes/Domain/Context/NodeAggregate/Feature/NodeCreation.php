<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature;

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
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNode;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateCurrentlyExists;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeTypeIsNotOfTypeRoot;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeTypeNotFound;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateRootNodeAggregateWithNode;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\PropertyCannotBeSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValue;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Infrastructure\Property\PropertyType;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeCreation
{
    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function getAllowedDimensionSubspace(): DimensionSpacePointSet;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType;

    abstract protected function requireNodeTypeToNotBeAbstract(NodeType $nodeType): void;

    abstract protected function requireNodeTypeToBeOfTypeRoot(NodeType $nodeType): void;

    abstract protected function getPropertyConverter(): PropertyConverter;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    /**
     * @param CreateRootNodeAggregateWithNode $command
     * @return CommandResult
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregateCurrentlyExists
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws NodeTypeNotFound
     * @throws NodeTypeIsNotOfTypeRoot
     */
    public function handleCreateRootNodeAggregateWithNode(CreateRootNodeAggregateWithNode $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->getContentStreamIdentifier());
        $this->requireProjectedNodeAggregateToNotExist($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $nodeType = $this->requireNodeType($command->getNodeTypeName());
        $this->requireNodeTypeToNotBeAbstract($nodeType);
        $this->requireNodeTypeToBeOfTypeRoot($nodeType);

        $events = DomainEvents::createEmpty();
        $this->getNodeAggregateEventPublisher()->withCommand($command, function () use ($command, &$events) {
            $events = $this->createRootWithNode(
                $command,
                $this->getAllowedDimensionSubspace()
            );
        });

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }

    /**
     * @param CreateRootNodeAggregateWithNode $command
     * @param DimensionSpacePointSet $coveredDimensionSpacePoints
     * @return DomainEvents
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    private function createRootWithNode(
        CreateRootNodeAggregateWithNode $command,
        DimensionSpacePointSet $coveredDimensionSpacePoints
    ): DomainEvents {
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new RootNodeAggregateWithNodeWasCreated(
                    $command->getContentStreamIdentifier(),
                    $command->getNodeAggregateIdentifier(),
                    $command->getNodeTypeName(),
                    $coveredDimensionSpacePoints,
                    NodeAggregateClassification::CLASSIFICATION_ROOT,
                    $command->getInitiatingUserIdentifier()
                ),
                Uuid::uuid4()->toString()
            )
        );

        $contentStreamEventStreamName = ContentStream\ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier());
        $this->getNodeAggregateEventPublisher()->publishMany(
            $contentStreamEventStreamName->getEventStreamName(),
            $events
        );

        return $events;
    }

    public function handleCreateNodeAggregateWithNode(CreateNodeAggregateWithNode $command): CommandResult
    {
        $this->requireNodeType($command->getNodeTypeName());
        $this->validateProperties($this->deserializeDefaultProperties($command->getNodeTypeName()), $command->getNodeTypeName());
        $this->validateProperties($command->getInitialPropertyValues(), $command->getNodeTypeName());

        $lowLevelCommand = new CreateNodeAggregateWithNodeAndSerializedProperties(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier(),
            $command->getNodeTypeName(),
            $command->getOriginDimensionSpacePoint(),
            $command->getInitiatingUserIdentifier(),
            $command->getParentNodeAggregateIdentifier(),
            $command->getSucceedingSiblingNodeAggregateIdentifier(),
            $command->getNodeName(),
            $command->getInitialPropertyValues()
                ? $this->getPropertyConverter()->serializePropertyValues(
                    $command->getInitialPropertyValues(),
                    $this->requireNodeType($command->getNodeTypeName())
                )
                : null,
            $command->getTetheredDescendantNodeAggregateIdentifiers()
        );

        return $this->handleCreateNodeAggregateWithNodeAndSerializedProperties($lowLevelCommand);
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
        // initialize node type
        $nodeType->getOptions();
        foreach ($propertyValues->getValues() as $propertyName => $propertyValue) {
            if (!isset($nodeType->getProperties()[$propertyName])) {
                throw PropertyCannotBeSet::becauseTheNodeTypeDoesNotDeclareIt(PropertyName::fromString($propertyName), $nodeTypeName);
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
     * @param CreateNodeAggregateWithNodeAndSerializedProperties $command
     * @return CommandResult
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function handleCreateNodeAggregateWithNodeAndSerializedProperties(CreateNodeAggregateWithNodeAndSerializedProperties $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->getContentStreamIdentifier());
        $this->requireDimensionSpacePointToExist($command->getOriginDimensionSpacePoint());
        $nodeType = $this->requireNodeType($command->getNodeTypeName());
        $this->requireNodeTypeToNotBeAbstract($nodeType);
        $this->requireNodeTypeToNotBeOfTypeRoot($nodeType);
        $this->requireTetheredDescendantNodeTypesToExist($nodeType);
        $this->requireTetheredDescendantNodeTypesToNotBeOfTypeRoot($nodeType);
        if ($this->areAncestorNodeTypeConstraintChecksEnabled()) {
            $this->requireConstraintsImposedByAncestorsAreMet($command->getContentStreamIdentifier(), $nodeType, $command->getNodeName(), [$command->getParentNodeAggregateIdentifier()]);
        }
        $this->requireProjectedNodeAggregateToNotExist($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $parentNodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getParentNodeAggregateIdentifier());
        if ($command->getSucceedingSiblingNodeAggregateIdentifier()) {
            $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getSucceedingSiblingNodeAggregateIdentifier());
        }
        $this->requireNodeAggregateToCoverDimensionSpacePoint($parentNodeAggregate, $command->getOriginDimensionSpacePoint());
        $specializations = $this->getInterDimensionalVariationGraph()->getSpecializationSet($command->getOriginDimensionSpacePoint());
        $coveredDimensionSpacePoints = $specializations->getIntersection($parentNodeAggregate->getCoveredDimensionSpacePoints());
        if ($command->getNodeName()) {
            $this->requireNodeNameToBeUnoccupied(
                $command->getContentStreamIdentifier(),
                $command->getNodeName(),
                $command->getParentNodeAggregateIdentifier(),
                $command->getOriginDimensionSpacePoint(),
                $coveredDimensionSpacePoints
            );
        }
        $descendantNodeAggregateIdentifiers = self::populateNodeAggregateIdentifiers($nodeType, $command->getTetheredDescendantNodeAggregateIdentifiers());
        // Write the auto-created descendant node aggregate identifiers back to the command; so that when rebasing the command, it stays
        // fully deterministic.
        $command = $command->withTetheredDescendantNodeAggregateIdentifiers($descendantNodeAggregateIdentifiers);

        foreach ($descendantNodeAggregateIdentifiers as $rawNodePath => $descendantNodeAggregateIdentifier) {
            $this->requireProjectedNodeAggregateToNotExist($command->getContentStreamIdentifier(), $descendantNodeAggregateIdentifier);
        }

        $events = DomainEvents::createEmpty();
        $this->getNodeAggregateEventPublisher()->withCommand($command, function () use ($command, $nodeType, $parentNodeAggregate, $coveredDimensionSpacePoints, $descendantNodeAggregateIdentifiers, &$events) {
            $defaultPropertyValues = SerializedPropertyValues::defaultFromNodeType($nodeType);
            $initialPropertyValues = $defaultPropertyValues->merge($command->getInitialPropertyValues());

            $events = $this->createRegularWithNode(
                $command,
                $coveredDimensionSpacePoints,
                $initialPropertyValues
            );

            $events = $this->handleTetheredChildNodes(
                $command,
                $nodeType,
                $coveredDimensionSpacePoints,
                $command->getNodeAggregateIdentifier(),
                $descendantNodeAggregateIdentifiers,
                $events
            );
        });

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }

    /**
     * @param CreateNodeAggregateWithNodeAndSerializedProperties $command
     * @param DimensionSpacePointSet $coveredDimensionSpacePoints
     * @param SerializedPropertyValues $initialPropertyValues
     * @return DomainEvents
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    private function createRegularWithNode(
        CreateNodeAggregateWithNodeAndSerializedProperties $command,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        SerializedPropertyValues $initialPropertyValues
    ): DomainEvents {
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new NodeAggregateWithNodeWasCreated(
                    $command->getContentStreamIdentifier(),
                    $command->getNodeAggregateIdentifier(),
                    $command->getNodeTypeName(),
                    $command->getOriginDimensionSpacePoint(),
                    $coveredDimensionSpacePoints,
                    $command->getParentNodeAggregateIdentifier(),
                    $command->getNodeName(),
                    $initialPropertyValues,
                    NodeAggregateClassification::CLASSIFICATION_REGULAR,
                    $command->getInitiatingUserIdentifier(),
                    $command->getSucceedingSiblingNodeAggregateIdentifier()
                ),
                Uuid::uuid4()->toString()
            )
        );

        $contentStreamEventStreamName = ContentStream\ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier());
        $this->getNodeAggregateEventPublisher()->publishMany(
            $contentStreamEventStreamName->getEventStreamName(),
            $events
        );

        return $events;
    }

    /**
     * @param CreateNodeAggregateWithNodeAndSerializedProperties $command
     * @param NodeType $nodeType
     * @param DimensionSpacePointSet $coveredDimensionSpacePoints
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers
     * @param DomainEvents $events
     * @param NodePath|null $nodePath
     * @return DomainEvents
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
        DomainEvents $events,
        NodePath $nodePath = null
    ): DomainEvents {
        foreach ($nodeType->getAutoCreatedChildNodes() as $rawNodeName => $childNodeType) {
            $nodeName = NodeName::fromString($rawNodeName);
            $childNodePath = $nodePath ? $nodePath->appendPathSegment($nodeName) : NodePath::fromString((string) $nodeName);
            $childNodeAggregateIdentifier = $nodeAggregateIdentifiers->getNodeAggregateIdentifier($childNodePath) ?? NodeAggregateIdentifier::create();
            $initialPropertyValues = SerializedPropertyValues::defaultFromNodeType($childNodeType);

            $this->requireContentStreamToExist($command->getContentStreamIdentifier());
            $events = $events->appendEvents($this->createTetheredWithNode(
                $command,
                $childNodeAggregateIdentifier,
                NodeTypeName::fromString($childNodeType->getName()),
                $coveredDimensionSpacePoints,
                $parentNodeAggregateIdentifier,
                $nodeName,
                $initialPropertyValues
            ));

            $events = $this->handleTetheredChildNodes(
                $command,
                $childNodeType,
                $coveredDimensionSpacePoints,
                $childNodeAggregateIdentifier,
                $nodeAggregateIdentifiers,
                $events,
                $childNodePath
            );
        }

        return $events;
    }

    /**
     * @param CreateNodeAggregateWithNodeAndSerializedProperties $command
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param NodeTypeName $nodeTypeName
     * @param DimensionSpacePointSet $coveredDimensionSpacePoints
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $nodeName
     * @param SerializedPropertyValues $initialPropertyValues
     * @param NodeAggregateIdentifier|null $precedingNodeAggregateIdentifier
     * @return DomainEvents
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
    ): DomainEvents {
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new NodeAggregateWithNodeWasCreated(
                    $command->getContentStreamIdentifier(),
                    $nodeAggregateIdentifier,
                    $nodeTypeName,
                    $command->getOriginDimensionSpacePoint(),
                    $coveredDimensionSpacePoints,
                    $parentNodeAggregateIdentifier,
                    $nodeName,
                    $initialPropertyValues,
                    NodeAggregateClassification::CLASSIFICATION_TETHERED,
                    $command->getInitiatingUserIdentifier(),
                    $precedingNodeAggregateIdentifier
                ),
                Uuid::uuid4()->toString()
            )
        );

        $contentStreamEventStreamName = ContentStream\ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier());
        $this->getNodeAggregateEventPublisher()->publishMany(
            $contentStreamEventStreamName->getEventStreamName(),
            $events
        );

        return $events;
    }

    /**
     * @param NodeType $nodeType
     * @param NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers
     * @param NodePath|null $childPath
     * @return NodeAggregateIdentifiersByNodePaths
     */
    protected static function populateNodeAggregateIdentifiers(NodeType $nodeType, ?NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers, NodePath $childPath = null): NodeAggregateIdentifiersByNodePaths
    {
        if ($nodeAggregateIdentifiers === null) {
            $nodeAggregateIdentifiers = NodeAggregateIdentifiersByNodePaths::createEmpty();
        }
        // TODO: handle Multiple levels of autocreated child nodes
        foreach ($nodeType->getAutoCreatedChildNodes() as $rawChildName => $childNodeType) {
            $childName = NodeName::fromString($rawChildName);
            $childPath = $childPath ? $childPath->appendPathSegment($childName) : NodePath::fromString((string) $childName);
            if (!$nodeAggregateIdentifiers->getNodeAggregateIdentifier($childPath)) {
                $nodeAggregateIdentifiers = $nodeAggregateIdentifiers->add($childPath, NodeAggregateIdentifier::create());
            }
        }

        return $nodeAggregateIdentifiers;
    }
}
