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
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
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
        $contentStreamId = $this->requireContentStream($command->workspaceName, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->originDimensionSpacePoint->toDimensionSpacePoint());
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate);
        $nodeTypeName = $nodeAggregate->nodeTypeName;

        $this->validateProperties($command->propertyValues, $nodeTypeName);

        $lowLevelCommand = SetSerializedNodeProperties::create(
            $command->workspaceName,
            $command->nodeAggregateId,
            $command->originDimensionSpacePoint,
            $this->getPropertyConverter()->serializePropertyValues(
                $command->propertyValues->withoutUnsets(),
                $this->requireNodeType($nodeTypeName)
            ),
            $command->propertyValues->getPropertiesToUnset()
        );

        return $this->handleSetSerializedNodeProperties($lowLevelCommand, $contentRepository);
    }

    private function handleSetSerializedNodeProperties(
        SetSerializedNodeProperties $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $contentStreamId = $this->requireContentStream($command->workspaceName, $contentRepository);
        // Check if node exists
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $nodeType = $this->requireNodeType($nodeAggregate->nodeTypeName);
        $this->requireNodeAggregateToOccupyDimensionSpacePoint($nodeAggregate, $command->originDimensionSpacePoint);
        $events = [];
        $propertyValuesByScope = $command->propertyValues->splitByScope($nodeType);
        foreach ($propertyValuesByScope as $scopeValue => $propertyValues) {
            $affectedOrigins = PropertyScope::from($scopeValue)->resolveAffectedOrigins(
                $command->originDimensionSpacePoint,
                $nodeAggregate,
                $this->interDimensionalVariationGraph
            );
            foreach ($affectedOrigins as $affectedOrigin) {
                $events[] = new NodePropertiesWereSet(
                    $contentStreamId,
                    $command->nodeAggregateId,
                    $affectedOrigin,
                    $nodeAggregate->getCoverageByOccupant($affectedOrigin),
                    $propertyValues,
                    PropertyNames::createEmpty()
                );
            }
        }

        $propertiesToUnsetByScope = $this->splitPropertiesToUnsetByScope($command->propertiesToUnset, $nodeType);
        foreach ($propertiesToUnsetByScope as $scopeValue => $propertyNamesToUnset) {
            $affectedOrigins = PropertyScope::from($scopeValue)->resolveAffectedOrigins(
                $command->originDimensionSpacePoint,
                $nodeAggregate,
                $this->interDimensionalVariationGraph
            );
            foreach ($affectedOrigins as $affectedOrigin) {
                $events[] = new NodePropertiesWereSet(
                    $contentStreamId,
                    $command->nodeAggregateId,
                    $affectedOrigin,
                    $nodeAggregate->getCoverageByOccupant($affectedOrigin),
                    SerializedPropertyValues::createEmpty(),
                    $propertyNamesToUnset,
                );
            }
        }

        $events = $this->mergeSplitEvents($events);

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentStreamId)
                ->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                Events::fromArray($events)
            ),
            ExpectedVersion::ANY()
        );
    }

    /**
     * @return array<string,PropertyNames>
     */
    private function splitPropertiesToUnsetByScope(PropertyNames $propertiesToUnset, NodeType $nodeType): array
    {
        $propertiesToUnsetByScope = [];
        foreach ($propertiesToUnset as $propertyName) {
            $scope = PropertyScope::tryFromDeclaration($nodeType, $propertyName);
            $propertiesToUnsetByScope[$scope->value][] = $propertyName;
        }

        return array_map(
            fn(array $propertyValues): PropertyNames => PropertyNames::fromArray($propertyValues),
            $propertiesToUnsetByScope
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
