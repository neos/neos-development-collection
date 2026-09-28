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

namespace Neos\ContentRepository\Core\Feature\NodeMove;

use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\TargetLocationInSubgraph;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\RelationDistributionStrategy;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\RebaseableCommand;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsDescendant;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNoChild;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNoSibling;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeMove
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function requireNodeTypeNotToDeclareTetheredChildNodeName(NodeTypeName $nodeTypeName, NodeName $nodeName): void;

    abstract protected function requireProjectedNodeAggregate(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $nodeAggregateId,
    ): NodeAggregate;

    /**
     * @throws NodeAggregateCurrentlyDoesNotExist
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregateIsDescendant
     * @throws NodeAggregateIsNoSibling
     * @throws NodeAggregateIsNoChild
     */
    private function handleMoveNodeAggregate(
        MoveNodeAggregate $command,
    ): EventsToPublish {
        $contentGraph = $this->commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId());
        $this->requireDimensionSpacePointToExist($command->dimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId,
        );
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate);
        $this->requireNodeAggregateToBeUntethered($nodeAggregate);
        $this->requireNodeAggregateToCoverDimensionSpacePoint($nodeAggregate, $command->dimensionSpacePoint);

        $affectedDimensionSpacePoints = $this->resolveAffectedDimensionSpacePointSet(
            $nodeAggregate,
            $command->relationDistributionStrategy,
            $command->dimensionSpacePoint,
        );

        if ($command->newParentNodeAggregateId) {
            $this->requireConstraintsImposedByAncestorsAreMet(
                $contentGraph,
                $this->requireNodeType($nodeAggregate->nodeTypeName),
                [$command->newParentNodeAggregateId],
            );

            $newParentNodeAggregate = $this->requireProjectedNodeAggregate(
                $contentGraph,
                $command->newParentNodeAggregateId,
            );

            $this->requireNodeNameToBeUncovered(
                $contentGraph,
                $nodeAggregate->nodeName,
                $command->newParentNodeAggregateId,
                $command->nodeAggregateId,
            );
            if ($nodeAggregate->nodeName) {
                $this->requireNodeTypeNotToDeclareTetheredChildNodeName($newParentNodeAggregate->nodeTypeName, $nodeAggregate->nodeName);
            }

            $this->requireNodeAggregateToCoverDimensionSpacePoints(
                $newParentNodeAggregate,
                $affectedDimensionSpacePoints,
            );

            $this->requireNodeAggregateToNotBeDescendant(
                $contentGraph,
                $newParentNodeAggregate,
                $nodeAggregate,
            );
        }

        $targetLocation = TargetLocationInSubgraph::create(
            nodeAggregateId: $command->nodeAggregateId,
            dimensionSpacePoint: $command->dimensionSpacePoint,
            parentNodeAggregateId: $command->newParentNodeAggregateId,
            succeedingSiblingNodeAggregateId: $command->newSucceedingSiblingNodeAggregateId,
            precedingSiblingNodeAggregateId: $command->newPrecedingSiblingNodeAggregateId,
            contentGraph: $contentGraph,
        );

        $interdimensionalSiblings = InterdimensionalSiblings::fromTargetLocationForDimensionSpacePoints(
            $targetLocation,
            $affectedDimensionSpacePoints,
            $command->dimensionSpacePoint,
            $contentGraph,
        );

        if ($command->newParentNodeAggregateId) {
            if (
                $command->newSucceedingSiblingNodeAggregateId !== null
                && $command->newPrecedingSiblingNodeAggregateId === null
            ) {
                $interdimensionalSiblings = $interdimensionalSiblings->reduceToSiblingsWithNodeAggregateId();
            }
        } else {
            if ($command->newSucceedingSiblingNodeAggregateId || $command->newPrecedingSiblingNodeAggregateId) {
                if (
                    $command->newSucceedingSiblingNodeAggregateId !== null
                    || $command->newPrecedingSiblingNodeAggregateId === null
                ) {
                    $interdimensionalSiblings = $interdimensionalSiblings->reduceToSiblingsWithNodeAggregateId();
                }
                if (
                    $command->newPrecedingSiblingNodeAggregateId !== null
                    && $command->newSucceedingSiblingNodeAggregateId === null
                ) {
                    $interdimensionalSiblings = $interdimensionalSiblings->reduceToSiblingsWithNodeAggregateId();
                }
            }
        }

        $events = Events::with(
            new NodeAggregateWasMoved(
                $command->workspaceName,
                $contentGraph->getContentStreamId(),
                $command->nodeAggregateId,
                $command->newParentNodeAggregateId,
                $interdimensionalSiblings,
            )
        );

        $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamId(
            $contentGraph->getContentStreamId()
        );

        return EventsToPublish::createEventsForStreamAndExpectedVersion(
            $contentStreamEventStreamName->getEventStreamName(),
            RebaseableCommand::enrichWithCommand(
                $command,
                $events
            ),
            $expectedVersion
        );
    }

    private function resolveAffectedDimensionSpacePointSet(
        NodeAggregate $nodeAggregate,
        RelationDistributionStrategy $relationDistributionStrategy,
        DimensionSpacePoint $referenceDimensionSpacePoint
    ): DimensionSpacePointSet {
        return match ($relationDistributionStrategy) {
            RelationDistributionStrategy::STRATEGY_SCATTER =>
                new DimensionSpacePointSet([$referenceDimensionSpacePoint]),
            RelationDistributionStrategy::STRATEGY_GATHER_SPECIALIZATIONS =>
                $nodeAggregate->coveredDimensionSpacePoints->getIntersection(
                    $this->getInterDimensionalVariationGraph()->getSpecializationSet($referenceDimensionSpacePoint)
                ),
            default => $nodeAggregate->coveredDimensionSpacePoints,
        };
    }
}
