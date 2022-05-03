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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeVariant;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\DimensionSpacePointIsNotYetOccupied;
/** @codingStandardsIgnoreStart */
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
/** @codingStandardsIgnoreEnd */
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateCurrentlyExists;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventPublisher;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;

trait NodeVariation
{
    use NodeVariationInternals;
    use ConstraintChecks;

    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    /**
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregateCurrentlyExists
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws DimensionSpacePointIsNotYetOccupied
     * @throws DimensionSpacePointIsAlreadyOccupied
     * @throws NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint
     */
    public function handleCreateNodeVariant(CreateNodeVariant $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->contentStreamIdentifier);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier
        );
        $this->requireDimensionSpacePointToExist($command->sourceOrigin->toDimensionSpacePoint());
        $this->requireDimensionSpacePointToExist($command->targetOrigin->toDimensionSpacePoint());
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate);
        $this->requireNodeAggregateToBeUntethered($nodeAggregate);
        $this->requireNodeAggregateToOccupyDimensionSpacePoint($nodeAggregate, $command->sourceOrigin);
        $this->requireNodeAggregateToNotOccupyDimensionSpacePoint($nodeAggregate, $command->targetOrigin);
        $parentNodeAggregate = $this->requireProjectedParentNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $command->sourceOrigin
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $parentNodeAggregate,
            $command->targetOrigin->toDimensionSpacePoint()
        );

        $events = $this->createEventsForVariations(
            $command->contentStreamIdentifier,
            $command->sourceOrigin,
            $command->targetOrigin,
            $nodeAggregate,
            $command->initiatingUserIdentifier
        );

        $this->getNodeAggregateEventPublisher()->withCommand($command, function () use ($command, $events) {
            $streamName = ContentStream\ContentStreamEventStreamName::fromContentStreamIdentifier(
                $command->contentStreamIdentifier
            );

            $this->getNodeAggregateEventPublisher()->publishMany($streamName->getEventStreamName(), $events);
        });

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }
}
