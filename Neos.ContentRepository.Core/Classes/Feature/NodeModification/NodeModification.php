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

namespace Neos\ContentRepository\Core\Feature\NodeModification;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeModification
{
    abstract protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType;

    abstract protected function requireProjectedNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        ContentRepository $contentRepository
    ): NodeAggregate;

    private function handleSetNodeProperties(
        SetNodeProperties $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->originDimensionSpacePoint->toDimensionSpacePoint());
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate);
        $nodeTypeName = $nodeAggregate->nodeTypeName;

        $this->validateProperties($command->propertyValues, $nodeTypeName);

        $lowLevelCommand = new SetSerializedNodeProperties(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $command->originDimensionSpacePoint,
            $this->getPropertyConverter()->serializePropertyValues(
                $command->propertyValues,
                $this->requireNodeType($nodeTypeName)
            ),
        );

        return $this->handleSetSerializedNodeProperties($lowLevelCommand, $contentRepository);
    }

    private function handleSetSerializedNodeProperties(
        SetSerializedNodeProperties $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        // Check if node exists
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $nodeType = $this->requireNodeType($nodeAggregate->nodeTypeName);
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
                    $command->contentStreamId,
                    $command->nodeAggregateId,
                    $affectedOrigin,
                    $propertyValues,
                );
            }
        }
        $events = $this->mergeSplitEvents($events);

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($command->contentStreamId)
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
