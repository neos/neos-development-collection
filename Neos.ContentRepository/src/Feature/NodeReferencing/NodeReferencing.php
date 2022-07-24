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

namespace Neos\ContentRepository\Feature\NodeReferencing;

use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Feature\Common\PropertyScope;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

trait NodeReferencing
{
    use ConstraintChecks;

    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    /**
     * @param SetNodeReferences $command
     * @return EventsToPublish
     * @throws \Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    private function handleSetNodeReferences(SetNodeReferences $command): EventsToPublish
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->contentStreamIdentifier);
        $this->requireDimensionSpacePointToExist(
            $command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint()
        );
        $sourceNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->sourceNodeAggregateIdentifier
        );
        $this->requireNodeAggregateToNotBeRoot($sourceNodeAggregate);
        $this->requireNodeAggregateToOccupyDimensionSpacePoint(
            $sourceNodeAggregate,
            $command->sourceOriginDimensionSpacePoint
        );
        $this->requireNodeTypeToDeclareReference($sourceNodeAggregate->getNodeTypeName(), $command->referenceName);
        foreach ($command->destinationNodeAggregateIdentifiers as $destinationNodeAggregateIdentifier) {
            $destinationNodeAggregate = $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $destinationNodeAggregateIdentifier
            );
            $this->requireNodeAggregateToNotBeRoot($destinationNodeAggregate);
            $this->requireNodeAggregateToCoverDimensionSpacePoint(
                $destinationNodeAggregate,
                $command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint()
            );
            $this->requireNodeTypeToAllowNodesOfTypeInReference(
                $sourceNodeAggregate->getNodeTypeName(),
                $command->referenceName,
                $destinationNodeAggregate->getNodeTypeName()
            );
        }

        $events = [];
        $sourceNodeType = $this->requireNodeType($sourceNodeAggregate->getNodeTypeName());
        $declaration = $sourceNodeType->getProperties()[$command->referenceName->value]['scope'] ?? null;
        if (is_string($declaration)) {
            $scope = PropertyScope::from($declaration);
        } else {
            $scope = PropertyScope::SCOPE_NODE;
        }
        $affectedOrigins = $scope->resolveAffectedOrigins(
            $command->sourceOriginDimensionSpacePoint,
            $sourceNodeAggregate,
            $this->interDimensionalVariationGraph
        );
        foreach ($affectedOrigins as $originDimensionSpacePoint) {
            $events[] = new NodeReferencesWereSet(
                $command->contentStreamIdentifier,
                $command->sourceNodeAggregateIdentifier,
                $originDimensionSpacePoint,
                $command->destinationNodeAggregateIdentifiers,
                $command->referenceName,
                $command->initiatingUserIdentifier
            );
        }

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
}
