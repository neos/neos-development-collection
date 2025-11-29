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

use Neos\ContentRepository\Core\CommandHandler\CommandHandlingDependencies;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSibling;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblingsProvider;
use Neos\ContentRepository\Core\Feature\RebaseableCommand;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\RelationDistributionStrategy;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
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
    use InterdimensionalSiblingsProvider;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function requireNodeTypeNotToDeclareTetheredChildNodeName(NodeTypeName $nodeTypeName, NodeName $nodeName): void;

    abstract protected function requireProjectedNodeAggregate(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $nodeAggregateId,
    ): NodeAggregate;

    abstract protected function requireNodeAggregateToBeSibling(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $referenceNodeAggregateId,
        NodeAggregateId $siblingNodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint,
    ): void;

    abstract protected function requireNodeAggregateToBeChild(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $childNodeAggregateId,
        NodeAggregateId $parentNodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint,
    ): void;

    /**
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregateCurrentlyDoesNotExist
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregateIsDescendant
     * @throws NodeAggregateIsNoSibling
     * @throws NodeAggregateIsNoChild
     */
    private function handleMoveNodeAggregate(
        MoveNodeAggregate $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $contentStreamId = $this->requireContentStream($command->workspaceName, $commandHandlingDependencies);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentStreamId, $commandHandlingDependencies);
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
            $command->dimensionSpacePoint
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
                $affectedDimensionSpacePoints
            );

            $this->requireNodeAggregateToNotBeDescendant(
                $contentGraph,
                $newParentNodeAggregate,
                $nodeAggregate,
            );
        }

        if ($command->newPrecedingSiblingNodeAggregateId) {
            $this->requireProjectedNodeAggregate(
                $contentGraph,
                $command->newPrecedingSiblingNodeAggregateId,
            );
            if ($command->newParentNodeAggregateId) {
                $this->requireNodeAggregateToBeChild(
                    $contentGraph,
                    $command->newPrecedingSiblingNodeAggregateId,
                    $command->newParentNodeAggregateId,
                    $command->dimensionSpacePoint,
                );
            } else {
                $this->requireNodeAggregateToBeSibling(
                    $contentGraph,
                    $command->nodeAggregateId,
                    $command->newPrecedingSiblingNodeAggregateId,
                    $command->dimensionSpacePoint,
                );
            }
        }
        if ($command->newSucceedingSiblingNodeAggregateId) {
            $this->requireProjectedNodeAggregate(
                $contentGraph,
                $command->newSucceedingSiblingNodeAggregateId,
            );
            if ($command->newParentNodeAggregateId) {
                $this->requireNodeAggregateToBeChild(
                    $contentGraph,
                    $command->newSucceedingSiblingNodeAggregateId,
                    $command->newParentNodeAggregateId,
                    $command->dimensionSpacePoint,
                );
            } else {
                $this->requireNodeAggregateToBeSibling(
                    $contentGraph,
                    $command->nodeAggregateId,
                    $command->newSucceedingSiblingNodeAggregateId,
                    $command->dimensionSpacePoint,
                );
            }
        }

        $events = Events::with(
            new NodeAggregateWasMoved(
                workspaceName: $command->workspaceName,
                contentStreamId: $contentStreamId,
                nodeAggregateId: $command->nodeAggregateId,
                newParentNodeAggregateId: $command->newParentNodeAggregateId,
                succeedingSiblingsForCoverage: $this->resolveInterdimensionalSiblings(
                    contentGraph: $contentGraph,
                    selectedDimensionSpacePoint: $command->dimensionSpacePoint,
                    affectedDimensionSpacePoints: $affectedDimensionSpacePoints,
                    nodeAggregateId: $command->nodeAggregateId,
                    parentNodeAggregateId: $command->newParentNodeAggregateId,
                    precedingSiblingNodeAggregateId: $command->newPrecedingSiblingNodeAggregateId,
                    succeedingSiblingNodeAggregateId: $command->newSucceedingSiblingNodeAggregateId,
                    completeSet: $command->newParentNodeAggregateId !== null
                    || ($command->newPrecedingSiblingNodeAggregateId === null && $command->newSucceedingSiblingNodeAggregateId === null)
                )
            )
        );

        $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamId(
            $contentStreamId
        );

        return new EventsToPublish(
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
        Dto\RelationDistributionStrategy $relationDistributionStrategy,
        DimensionSpace\DimensionSpacePoint $referenceDimensionSpacePoint
    ): DimensionSpacePointSet {
        return match ($relationDistributionStrategy) {
            Dto\RelationDistributionStrategy::STRATEGY_SCATTER =>
            new DimensionSpacePointSet([$referenceDimensionSpacePoint]),
            RelationDistributionStrategy::STRATEGY_GATHER_SPECIALIZATIONS =>
            $nodeAggregate->coveredDimensionSpacePoints->getIntersection(
                $this->getInterDimensionalVariationGraph()->getSpecializationSet($referenceDimensionSpacePoint)
            ),
            default => $nodeAggregate->coveredDimensionSpacePoints,
        };
    }
}
