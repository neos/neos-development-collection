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

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
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
use Neos\EventStore\Model\EventStream\ExpectedVersion;

trait NodeModification
{
    abstract protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType;

    abstract protected function requireProjectedNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ContentRepository $contentRepository
    ): ReadableNodeAggregateInterface;

    private function handleSetNodeProperties(SetNodeProperties $command, ContentRepository $contentRepository): EventsToPublish
    {
        $this->requireContentStreamToExist($command->contentStreamIdentifier, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->originDimensionSpacePoint->toDimensionSpacePoint());
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $contentRepository
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

        return $this->handleSetSerializedNodeProperties($lowLevelCommand, $contentRepository);
    }

    private function handleSetSerializedNodeProperties(SetSerializedNodeProperties $command, ContentRepository $contentRepository): EventsToPublish
    {
        // Check if node exists
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $contentRepository
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

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
                ->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                Events::fromArray($events)
            ),
            ExpectedVersion::ANY()
        );
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
