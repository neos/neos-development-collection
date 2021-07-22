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

use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeReferences;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeReferencesWereSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventPublisher;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeReferencing
{
    use ConstraintChecks;

    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    /**
     * @param SetNodeReferences $command
     * @return CommandResult
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function handleSetNodeReferences(SetNodeReferences $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->getContentStreamIdentifier());
        $this->requireDimensionSpacePointToExist($command->getSourceOriginDimensionSpacePoint());
        $sourceNodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getSourceNodeAggregateIdentifier());
        $this->requireNodeAggregateToOccupyDimensionSpacePoint($sourceNodeAggregate, $command->getSourceOriginDimensionSpacePoint());
        $this->requireNodeTypeToDeclareReference($sourceNodeAggregate->getNodeTypeName(), $command->getReferenceName());
        foreach ($command->getDestinationNodeAggregateIdentifiers() as $destinationNodeAggregateIdentifier) {
            $destinationNodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $destinationNodeAggregateIdentifier);
            // @todo check reference node type constraints
        }

        $events = null;
        $this->getNodeAggregateEventPublisher()->withCommand($command, function () use ($command, &$events) {
            $events = DomainEvents::withSingleEvent(
                DecoratedEvent::addIdentifier(
                    new NodeReferencesWereSet(
                        $command->getContentStreamIdentifier(),
                        $command->getSourceNodeAggregateIdentifier(),
                        $command->getSourceOriginDimensionSpacePoint(),
                        $command->getDestinationNodeAggregateIdentifiers(),
                        $command->getReferenceName(),
                        $command->getInitiatingUserIdentifier()
                    ),
                    Uuid::uuid4()->toString()
                )
            );
            $this->getNodeAggregateEventPublisher()->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier())->getEventStreamName(),
                $events
            );
        });

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }
}
