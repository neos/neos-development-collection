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

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature;

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetSerializedNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePropertiesWereSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\PropertyScope;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeModification
{
    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType;

    abstract protected function requireProjectedNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ReadableNodeAggregateInterface;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    public function handleSetNodeProperties(SetNodeProperties $command): CommandResult
    {
        $this->requireContentStreamToExist($command->contentStreamIdentifier);
        $this->requireDimensionSpacePointToExist($command->originDimensionSpacePoint->toDimensionSpacePoint());
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier
        );
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate);
        $nodeTypeName = $nodeAggregate->getNodeTypeName();

        $this->validateProperties($command->propertyValues, $nodeTypeName);

        $lowLevelCommand = new SetSerializedNodeProperties(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $command->originDimensionSpacePoint,
            $this->getPropertyConverter()->serializePropertyValues(
                $command->propertyValues,
                $this->requireNodeType($nodeTypeName)
            ),
            $command->initiatingUserIdentifier
        );

        return $this->handleSetSerializedNodeProperties($lowLevelCommand);
    }

    public function handleSetSerializedNodeProperties(SetSerializedNodeProperties $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $domainEvents = null;
        $this->getNodeAggregateEventPublisher()->withCommand($command, function () use ($command, &$domainEvents) {
            // Check if node exists
            $nodeAggregate = $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $command->nodeAggregateIdentifier
            );
            $nodeType = $this->requireNodeType($nodeAggregate->getNodeTypeName());
            $this->requireNodeAggregateToOccupyDimensionSpacePoint($nodeAggregate, $command->originDimensionSpacePoint);
            $propertyValuesByScope = $this->splitPropertiesByScope($command->propertyValues, $nodeType);
            $events = [];
            foreach ($propertyValuesByScope as $scopeValue => $propertyValues) {
                $scope = PropertyScope::from($scopeValue);
                $affectedOrigins = match($scope) {
                    PropertyScope::SCOPE_NODE => new OriginDimensionSpacePointSet([$command->originDimensionSpacePoint]),
                    PropertyScope::SCOPE_SPECIALIZATIONS => OriginDimensionSpacePointSet::fromDimensionSpacePointSet(
                        $this->getInterDimensionalVariationGraph()->getSpecializationSet(
                            $command->originDimensionSpacePoint->toDimensionSpacePoint()
                        )
                    )->getIntersection($nodeAggregate->getOccupiedDimensionSpacePoints()),
                    PropertyScope::SCOPE_NODE_AGGREGATE => $nodeAggregate->getOccupiedDimensionSpacePoints()
                };
                foreach ($affectedOrigins as $affectedOrigin) {
                    $events[] = DecoratedEvent::addIdentifier(
                        new NodePropertiesWereSet(
                            $command->contentStreamIdentifier,
                            $command->nodeAggregateIdentifier,
                            $affectedOrigin,
                            $propertyValues,
                            $command->initiatingUserIdentifier
                        ),
                        Uuid::uuid4()->toString()
                    );
                }
            }

            $domainEvents = DomainEvents::fromArray($events);

            $this->getNodeAggregateEventPublisher()->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
                    ->getEventStreamName(),
                $domainEvents
            );
        });
        /** @var DomainEvents $domainEvents */

        return CommandResult::fromPublishedEvents($domainEvents, $this->getRuntimeBlocker());
    }

    /**
     * @return array<string,SerializedPropertyValues>
     */
    private function splitPropertiesByScope(SerializedPropertyValues $propertyValues, NodeType $nodeType): array
    {
        $propertyValuesByScope = [];
        foreach ($propertyValues as $propertyName => $propertyValue) {
            $declaration = $nodeType->getProperties()[$propertyName]['scope'] ?? null;
            if (is_string($declaration)) {
                $scope = PropertyScope::from($declaration);
            } else {
                $scope = PropertyScope::SCOPE_NODE;
            }
            $propertyValuesByScope[$scope->value][$propertyName] = $propertyValue;
        }

        return array_map(
            fn(array $propertyValues): SerializedPropertyValues => SerializedPropertyValues::fromArray($propertyValues),
            $propertyValuesByScope
        );
    }
}
