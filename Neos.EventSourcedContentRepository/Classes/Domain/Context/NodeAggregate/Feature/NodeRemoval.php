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
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasRemoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\TetheredNodeAggregateCannotBeRemoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeRemoval
{
    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    /**
     * @param RemoveNodeAggregate $command
     * @return CommandResult
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointNotFound
     */
    public function handleRemoveNodeAggregate(RemoveNodeAggregate $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->getContentStreamIdentifier());
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier()
        );
        $this->requireDimensionSpacePointToExist($command->getCoveredDimensionSpacePoint());
        $this->requireNodeAggregateNotToBeTethered($nodeAggregate);
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->getCoveredDimensionSpacePoint()
        );

        $events = null;
        $this->getNodeAggregateEventPublisher()->withCommand(
            $command,
            function () use ($command, $nodeAggregate, &$events) {
                $events = DomainEvents::withSingleEvent(
                    DecoratedEvent::addIdentifier(
                        new NodeAggregateWasRemoved(
                            $command->getContentStreamIdentifier(),
                            $command->getNodeAggregateIdentifier(),
                            $command->getNodeVariantSelectionStrategy()->resolveAffectedOriginDimensionSpacePoints(
                                $nodeAggregate->getOccupationByCovered($command->getCoveredDimensionSpacePoint()),
                                $nodeAggregate,
                                $this->getInterDimensionalVariationGraph()
                            ),
                            $command->getNodeVariantSelectionStrategy()->resolveAffectedDimensionSpacePoints(
                                    $command->getCoveredDimensionSpacePoint(),
                                    $nodeAggregate,
                                    $this->getInterDimensionalVariationGraph()
                                ),
                            $command->getInitiatingUserIdentifier(),
                            $command->getRemovalAttachmentPoint()
                        ),
                        Uuid::uuid4()->toString()
                    )
                );

                $this->getNodeAggregateEventPublisher()->publishMany(
                    ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier())
                        ->getEventStreamName(),
                    $events
                );
            }
        );
        /** @var DomainEvents $events */

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }

    protected function requireNodeAggregateNotToBeTethered(ReadableNodeAggregateInterface $nodeAggregate): void
    {
        if ($nodeAggregate->isTethered()) {
            throw new TetheredNodeAggregateCannotBeRemoved(
                'The node aggregate "' . $nodeAggregate->getIdentifier() . '" is tethered, and thus cannot be removed.',
                1597753832
            );
        }
    }
}
