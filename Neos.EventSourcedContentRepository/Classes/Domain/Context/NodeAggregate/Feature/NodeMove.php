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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\MoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasMoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateIsDescendant;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\RelationDistributionStrategy;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeMoveMapping;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeMoveMappings;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\Decorator\EventWithIdentifier;
use Neos\EventSourcing\Event\DomainEvents;

trait NodeMove
{
    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function requireProjectedNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ReadableNodeAggregateInterface;

    /**
     * @param MoveNodeAggregate $command
     * @return CommandResult
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws NodeAggregateCurrentlyDoesNotExist
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregateIsDescendant
     */
    public function handleMoveNodeAggregate(MoveNodeAggregate $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->getContentStreamIdentifier());
        $this->requireDimensionSpacePointToExist($command->getDimensionSpacePoint());
        $nodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate);
        $this->requireNodeAggregateToBeUntethered($nodeAggregate);
        $this->requireNodeAggregateToCoverDimensionSpacePoint($nodeAggregate, $command->getDimensionSpacePoint());

        $events = null;
        $this->getNodeAggregateEventPublisher()->withCommand($command, function () use ($command, $nodeAggregate, &$events) {
            switch ($command->getRelationDistributionStrategy()->getStrategy()) {
                case RelationDistributionStrategy::STRATEGY_SCATTER:
                    $affectedDimensionSpacePoints = new DimensionSpacePointSet([$command->getDimensionSpacePoint()]);
                    break;
                case RelationDistributionStrategy::STRATEGY_GATHER_SPECIALIZATIONS:
                    $affectedDimensionSpacePoints = $nodeAggregate->getCoveredDimensionSpacePoints()->getIntersection(
                        $this->getInterDimensionalVariationGraph()->getSpecializationSet($command->getDimensionSpacePoint())
                    );
                    break;
                case RelationDistributionStrategy::STRATEGY_GATHER_ALL:
                default:
                    $affectedDimensionSpacePoints = $nodeAggregate->getCoveredDimensionSpacePoints();
            }

            $newParentNodeAggregate = null;
            if ($command->getNewParentNodeAggregateIdentifier()) {
                $this->requireConstraintsImposedByAncestorsAreMet(
                    $command->getContentStreamIdentifier(),
                    $this->requireNodeType($nodeAggregate->getNodeTypeName()),
                    $nodeAggregate->getNodeName(),
                    [$command->getNewParentNodeAggregateIdentifier()]
                );

                $this->requireNodeNameToBeUncovered(
                    $command->getContentStreamIdentifier(),
                    $nodeAggregate->getNodeName(),
                    $command->getNewParentNodeAggregateIdentifier(),
                    $affectedDimensionSpacePoints
                );

                $newParentNodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getNewParentNodeAggregateIdentifier());

                // @todo new parent must cover all affected DSPs

                $this->requireNodeAggregateToNotBeDescendant($command->getContentStreamIdentifier(), $newParentNodeAggregate, $nodeAggregate);
            }

            $newSucceedingSiblingNodeAggregate = null;
            if ($command->getNewSucceedingSiblingNodeAggregateIdentifier()) {
                $newSucceedingSiblingNodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getNewSucceedingSiblingNodeAggregateIdentifier());
            }

            $nodeMoveMappings = $this->getNodeMoveMappings($nodeAggregate, $newParentNodeAggregate, $newSucceedingSiblingNodeAggregate, $affectedDimensionSpacePoints);

            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodeAggregateWasMoved(
                        $command->getContentStreamIdentifier(),
                        $command->getNodeAggregateIdentifier(),
                        $command->getNewParentNodeAggregateIdentifier(),
                        $command->getNewSucceedingSiblingNodeAggregateIdentifier(),
                        $nodeMoveMappings,
                        $affectedDimensionSpacePoints
                    )
                )
            );

            $contentStreamEventStreamName = ContentStream\ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier());
            $this->getNodeAggregateEventPublisher()->publishMany(
                $contentStreamEventStreamName->getEventStreamName(),
                $events
            );
        });

        return CommandResult::fromPublishedEvents($events);
    }

    protected function getNodeMoveMappings(
        ReadableNodeAggregateInterface $nodeAggregate,
        ?ReadableNodeAggregateInterface $newParentNodeAggregate,
        ?ReadableNodeAggregateInterface $newSucceedingSiblingNodeAggregate,
        ?DimensionSpacePointSet $affectedDimensionSpacePoints
    ): NodeMoveMappings {
        $nodeMoveMappings = [];
        foreach ($nodeAggregate->getOccupiedDimensionSpacePoints()->getIntersection($affectedDimensionSpacePoints) as $occupiedAffectedDimensionSpacePoint) {
            $nodeMoveMappings[] = new NodeMoveMapping(
                $occupiedAffectedDimensionSpacePoint,
                $newParentNodeAggregate
                    ? $newParentNodeAggregate->getOccupationByCovered($occupiedAffectedDimensionSpacePoint)
                    : null,
                $newSucceedingSiblingNodeAggregate
                    ? $newSucceedingSiblingNodeAggregate->getOccupationByCovered($occupiedAffectedDimensionSpacePoint)
                    : null,
                $newParentNodeAggregate
                    ? $newParentNodeAggregate->getCoverageByOccupant($newParentNodeAggregate->getOccupationByCovered($occupiedAffectedDimensionSpacePoint))->getIntersection($affectedDimensionSpacePoints)
                    : null
            );
        }

        return NodeMoveMappings::fromArray($nodeMoveMappings);
    }
}
