<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Feature\NodeDisabling;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeDisabling
{
    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    /**
     * @param DisableNodeAggregate $command
     * @return CommandResult
     * @throws ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregateCurrentlyDoesNotExist
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function handleDisableNodeAggregate(DisableNodeAggregate $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->contentStreamIdentifier);
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );
        $this->requireNodeAggregateToNotDisableDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );

        $events = null;
        $this->getNodeAggregateEventPublisher()->withCommand(
            $command,
            function () use ($command, $nodeAggregate, &$events) {
                $affectedDimensionSpacePoints = $command->nodeVariantSelectionStrategy
                    ->resolveAffectedDimensionSpacePoints(
                        $command->coveredDimensionSpacePoint,
                        $nodeAggregate,
                        $this->getInterDimensionalVariationGraph()
                    );

                $events = DomainEvents::withSingleEvent(
                    DecoratedEvent::addIdentifier(
                        new NodeAggregateWasDisabled(
                            $command->contentStreamIdentifier,
                            $command->nodeAggregateIdentifier,
                            $affectedDimensionSpacePoints,
                            $command->initiatingUserIdentifier
                        ),
                        Uuid::uuid4()->toString()
                    )
                );

                $this->getNodeAggregateEventPublisher()->publishMany(
                    ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
                        ->getEventStreamName(),
                    $events
                );
            }
        );
        /** @var DomainEvents $events */

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }

    /**
     * @param EnableNodeAggregate $command
     * @return CommandResult
     * @throws ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function handleEnableNodeAggregate(EnableNodeAggregate $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->contentStreamIdentifier);
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );
        $this->requireNodeAggregateToDisableDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );

        $events = null;
        $this->getNodeAggregateEventPublisher()->withCommand(
            $command,
            function () use ($command, $nodeAggregate, &$events) {
                $affectedDimensionSpacePoints = $command->nodeVariantSelectionStrategy
                    ->resolveAffectedDimensionSpacePoints(
                        $command->coveredDimensionSpacePoint,
                        $nodeAggregate,
                        $this->getInterDimensionalVariationGraph()
                    );

                $contentStreamIdentifier = $command->contentStreamIdentifier;

                $events = DomainEvents::withSingleEvent(
                    DecoratedEvent::addIdentifier(
                        new NodeAggregateWasEnabled(
                            $command->contentStreamIdentifier,
                            $command->nodeAggregateIdentifier,
                            $affectedDimensionSpacePoints,
                            $command->initiatingUserIdentifier
                        ),
                        Uuid::uuid4()->toString()
                    )
                );

                $this->getNodeAggregateEventPublisher()->publishMany(
                    ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)
                        ->getEventStreamName(),
                    $events
                );
            }
        );
        /** @var DomainEvents $events */

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }
}
