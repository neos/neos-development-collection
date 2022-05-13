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

namespace Neos\ContentRepository\Feature\NodeModification;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Feature\Common\PropertyScope;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
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

        $domainEvents = DomainEvents::createEmpty();
        $this->getNodeAggregateEventPublisher()->withCommand($command, function () use ($command, &$domainEvents) {
            // Check if node exists
            $nodeAggregate = $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $command->nodeAggregateIdentifier
            );
            $nodeType = $this->requireNodeType($nodeAggregate->getNodeTypeName());
            $this->requireNodeAggregateToOccupyDimensionSpacePoint($nodeAggregate, $command->originDimensionSpacePoint);
            $propertyValuesByScope = $command->propertyValues->splitByScope($nodeType);
            $events = [];
            foreach ($propertyValuesByScope as $scopeValue => $propertyValues) {
                $scope = PropertyScope::from($scopeValue);
                $affectedOrigins = $scope->resolveAffectedOrigins(
                    $command->originDimensionSpacePoint,
                    $nodeAggregate,
                    $this->interDimensionalVariationGraph
                );
                foreach ($affectedOrigins as $affectedOrigin) {
                    $events[] = new NodePropertiesWereSet(
                        $command->contentStreamIdentifier,
                        $command->nodeAggregateIdentifier,
                        $affectedOrigin,
                        $propertyValues,
                        $command->initiatingUserIdentifier
                    );
                }
            }
            $events = $this->mergeSplitEvents($events);
            $domainEvents = DomainEvents::fromArray(array_map(
                fn(NodePropertiesWereSet $event): DecoratedEvent => DecoratedEvent::addIdentifier(
                    $event,
                    Uuid::uuid4()->toString()
                ),
                $events
            ));

            $this->getNodeAggregateEventPublisher()->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
                    ->getEventStreamName(),
                $domainEvents
            );
        });

        return CommandResult::fromPublishedEvents($domainEvents, $this->getRuntimeBlocker());
    }

    /**
     * @param array<int,NodePropertiesWereSet> $events
     * @return array<int,NodePropertiesWereSet>
     */
    private function mergeSplitEvents(array $events): array
    {
        /** @var array<string,NodePropertiesWereSet> $eventsByOrigin */
        $eventsByOrigin = [];
        foreach ($events as $domainEvent) {
            if (!isset($eventsByOrigin[$domainEvent->originDimensionSpacePoint->hash])) {
                $eventsByOrigin[$domainEvent->originDimensionSpacePoint->hash] = $domainEvent;
            } else {
                $eventsByOrigin[$domainEvent->originDimensionSpacePoint->hash]
                    = $eventsByOrigin[$domainEvent->originDimensionSpacePoint->hash]->mergeProperties($domainEvent);
            }
        }

        return array_values($eventsByOrigin);
    }
}
