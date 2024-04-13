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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMappings;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMappings;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\ParentNodeMoveDestination;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\RelationDistributionStrategy;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\SucceedingSiblingNodeMoveDestination;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsDescendant;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeMove
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function requireProjectedNodeAggregate(
        ContentGraphAdapterInterface $contentGraphAdapter,
        NodeAggregateId $nodeAggregateId
    ): NodeAggregate;

    /**
     * @return EventsToPublish
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws NodeAggregateCurrentlyDoesNotExist
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregateIsDescendant
     */
    private function handleMoveNodeAggregate(
        MoveNodeAggregate $command
    ): EventsToPublish {
        $this->requireContentStream($command->workspaceName);
        $contentGraphAdapter = $this->getContentGraphAdapter($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraphAdapter);
        $this->requireDimensionSpacePointToExist($command->dimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraphAdapter,
            $command->nodeAggregateId
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
                $contentGraphAdapter,
                $this->requireNodeType($nodeAggregate->nodeTypeName),
                $nodeAggregate->nodeName,
                [$command->newParentNodeAggregateId]
            );

            $this->requireNodeNameToBeUncovered(
                $contentGraphAdapter,
                $nodeAggregate->nodeName,
                $command->newParentNodeAggregateId,
                $affectedDimensionSpacePoints
            );

            $newParentNodeAggregate = $this->requireProjectedNodeAggregate(
                $contentGraphAdapter,
                $command->newParentNodeAggregateId
            );

            $this->requireNodeAggregateToCoverDimensionSpacePoints(
                $newParentNodeAggregate,
                $affectedDimensionSpacePoints
            );

            $this->requireNodeAggregateToNotBeDescendant(
                $contentGraphAdapter,
                $newParentNodeAggregate,
                $nodeAggregate
            );
        }

        if ($command->newPrecedingSiblingNodeAggregateId) {
            $this->requireProjectedNodeAggregate(
                $contentGraphAdapter,
                $command->newPrecedingSiblingNodeAggregateId
            );
        }
        if ($command->newSucceedingSiblingNodeAggregateId) {
            $this->requireProjectedNodeAggregate(
                $contentGraphAdapter,
                $command->newSucceedingSiblingNodeAggregateId
            );
        }

        /** @var OriginNodeMoveMapping[] $originNodeMoveMappings */
        $originNodeMoveMappings = [];
        foreach ($nodeAggregate->occupiedDimensionSpacePoints as $movedNodeOrigin) {
            $originNodeMoveMappings[] = new OriginNodeMoveMapping(
                $movedNodeOrigin,
                $this->resolveCoverageNodeMoveMappings(
                    $contentGraphAdapter,
                    $nodeAggregate,
                    $command->newParentNodeAggregateId,
                    $command->newPrecedingSiblingNodeAggregateId,
                    $command->newSucceedingSiblingNodeAggregateId,
                    $movedNodeOrigin,
                    $affectedDimensionSpacePoints
                )
            );
        }

        $events = Events::with(
            new NodeAggregateWasMoved(
                $contentGraphAdapter->getContentStreamId(),
                $command->nodeAggregateId,
                OriginNodeMoveMappings::create(...$originNodeMoveMappings)
            )
        );

        $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamId(
            $contentGraphAdapter->getContentStreamId()
        );

        return new EventsToPublish(
            $contentStreamEventStreamName->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            $expectedVersion
        );
    }

    /**
     * Resolves the new parents on a per-dimension-space-point basis
     *
     * If no parent node aggregate is defined, it will be resolved from the already evaluated new succeeding siblings.
     *
     * @todo move to content graph for more efficient calculation, if possible
     */
    private function resolveNewParentAssignments(
        /** Adapter with workspace<>contentStream mapping the move operation is performed in */
        ContentGraphAdapterInterface $contentGraphAdapter,
        /** The parent node aggregate's id*/
        NodeAggregateId $parentId,
        DimensionSpace\DimensionSpacePoint $coveredDimensionSpacePoint
    ): CoverageNodeMoveMapping {
        $parentNode = $contentGraphAdapter->findNodeInSubgraph(
            $coveredDimensionSpacePoint,
            $parentId
        );
        if ($parentNode === null) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Parent ' . $parentId->value . ' not found in ontentstream "%s" and dimension space point "%s" ',
                    $contentGraphAdapter->getContentStreamId()->value,
                    json_encode($coveredDimensionSpacePoint, JSON_PARTIAL_OUTPUT_ON_ERROR)
                ),
                1667596931
            );
        }

        return CoverageNodeMoveMapping::createForNewParent(
            $coveredDimensionSpacePoint,
            ParentNodeMoveDestination::create(
                $parentId,
                $parentNode->originDimensionSpacePoint
            )
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

    private function findSiblingWithin(
        ContentGraphAdapterInterface $contentGraphAdapter,
        DimensionSpace\DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $siblingId,
        ?NodeAggregateId $parentId
    ): ?Node {
        $siblingCandidate = $contentGraphAdapter->findNodeInSubgraph($coveredDimensionSpacePoint, $siblingId);
        if (!$siblingCandidate) {
            return null;
        }

        if (!$parentId) {
            return $siblingCandidate;
        }

        $parent = $contentGraphAdapter->findParentNodeInSubgraph($coveredDimensionSpacePoint, $siblingId);
        if (is_null($parent)) {
            throw new \InvalidArgumentException(
                'Parent ' . $parentId->value . ' not found in subgraph ' . json_encode($coveredDimensionSpacePoint, JSON_PARTIAL_OUTPUT_ON_ERROR),
                1645366837
            );
        }
        if ($parent->nodeAggregateId->equals($parentId)) {
            return $siblingCandidate;
        }

        return null;
    }

    private function resolveSucceedingSiblingFromOriginSiblings(
        ContentGraphAdapterInterface $contentGraphAdapter,
        NodeAggregateId $nodeAggregateId,
        ?NodeAggregateId $parentId,
        ?NodeAggregateId $precedingSiblingId,
        ?NodeAggregateId $succeedingSiblingId,
        DimensionSpace\DimensionSpacePoint $currentDimensionSpacePoint,
        DimensionSpace\DimensionSpacePoint $originDimensionSpacePoint
    ): ?Node {
        $succeedingSibling = null;
        $precedingSiblingCandidates = iterator_to_array(
            $precedingSiblingId
                ? $contentGraphAdapter->findPreceedingSiblingNodesInSubgraph($originDimensionSpacePoint, $precedingSiblingId)
                : Nodes::createEmpty()
        );
        $succeedingSiblingCandidates = iterator_to_array(
            $succeedingSiblingId
                ? $contentGraphAdapter->findSucceedingSiblingNodesInSubgraph($originDimensionSpacePoint, $succeedingSiblingId)
                : Nodes::createEmpty()
        );
        /* @var $precedingSiblingCandidates Node[] */
        /* @var $succeedingSiblingCandidates Node[] */
        $maximumIndex = max(count($succeedingSiblingCandidates), count($precedingSiblingCandidates));
        for ($i = 0; $i < $maximumIndex; $i++) {
            // try successors of same distance first
            if (isset($succeedingSiblingCandidates[$i])) {
                if ($succeedingSiblingCandidates[$i]->nodeAggregateId->equals($nodeAggregateId)) {
                    \array_splice($succeedingSiblingCandidates, $i, 1);
                }
                $succeedingSibling = $this->findSiblingWithin(
                    $contentGraphAdapter,
                    $currentDimensionSpacePoint,
                    $succeedingSiblingCandidates[$i]->nodeAggregateId,
                    $parentId
                );
                if ($succeedingSibling) {
                    break;
                }
            }
            if (isset($precedingSiblingCandidates[$i])) {
                /** @var NodeAggregateId $precedingSiblingId can only be the case if not null */
                if ($precedingSiblingCandidates[$i]->nodeAggregateId->equals($nodeAggregateId)) {
                    \array_splice($precedingSiblingCandidates, $i, 1);
                }
                $precedingSibling = $this->findSiblingWithin(
                    $contentGraphAdapter,
                    $currentDimensionSpacePoint,
                    $precedingSiblingCandidates[$i]->nodeAggregateId,
                    $parentId
                );
                if ($precedingSibling) {
                    // TODO: I don't think implementing the same filtering as for the contentGraph is sensible here, so we are fetching all siblings while only interested in the next, maybe could become a more specialised method.
                    $alternateSucceedingSiblings = $contentGraphAdapter->findSucceedingSiblingNodesInSubgraph($currentDimensionSpacePoint, $precedingSiblingId);
                    if (count($alternateSucceedingSiblings) > 0) {
                        $succeedingSibling = $alternateSucceedingSiblings->first();
                        break;
                    }
                }
            }
        }

        return $succeedingSibling;
    }

    private function resolveCoverageNodeMoveMappings(
        /** Adapter with workspace<>contentStream mapping the move operation is performed in */
        ContentGraphAdapterInterface $contentGraphAdapter,
        /** The node aggregate to be moved */
        NodeAggregate $nodeAggregate,
        /** The parent node aggregate id, has precedence over siblings when in doubt */
        ?NodeAggregateId $parentId,
        /** The planned preceding sibling's node aggregate id */
        ?NodeAggregateId $precedingSiblingId,
        /** The planned succeeding sibling's node aggregate id */
        ?NodeAggregateId $succeedingSiblingId,
        /** A dimension space point occupied by the node aggregate to be moved */
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        /** The dimension space points affected by the move operation */
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): CoverageNodeMoveMappings {
        /** @var CoverageNodeMoveMapping[] $coverageNodeMoveMappings */
        $coverageNodeMoveMappings = [];

        foreach (
            $nodeAggregate->getCoverageByOccupant($originDimensionSpacePoint)
                ->getIntersection($affectedDimensionSpacePoints) as $dimensionSpacePoint
        ) {
            $succeedingSibling = $succeedingSiblingId
                ? $this->findSiblingWithin($contentGraphAdapter, $dimensionSpacePoint, $succeedingSiblingId, $parentId)
                : null;
            if (!$succeedingSibling) {
                $precedingSibling = $precedingSiblingId
                    ? $this->findSiblingWithin($contentGraphAdapter, $dimensionSpacePoint, $precedingSiblingId, $parentId)
                    : null;
                if ($precedingSiblingId && $precedingSibling) {
                    $alternateSucceedingSiblings = $contentGraphAdapter->findSucceedingSiblingNodesInSubgraph(
                        $dimensionSpacePoint,
                        $precedingSiblingId
                    );
                    if (count($alternateSucceedingSiblings) > 0) {
                        $succeedingSibling = $alternateSucceedingSiblings->first();
                    }
                } else {
                    $succeedingSibling = $this->resolveSucceedingSiblingFromOriginSiblings(
                        $contentGraphAdapter,
                        $nodeAggregate->nodeAggregateId,
                        $parentId,
                        $precedingSiblingId,
                        $succeedingSiblingId,
                        $dimensionSpacePoint,
                        $originDimensionSpacePoint->toDimensionSpacePoint()
                    );
                }
            }

            if ($succeedingSibling) {
                // for the event payload, we additionally need the parent of the succeeding sibling
                $parentOfSucceedingSibling = $contentGraphAdapter->findParentNodeInSubgraph($dimensionSpacePoint, $succeedingSibling->nodeAggregateId);
                if ($parentOfSucceedingSibling === null) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Parent of succeeding sibling ' . $succeedingSibling->nodeAggregateId->value . ' not found in contentstream "%s" and dimension space point "%s" ',
                            $contentGraphAdapter->getContentStreamId()->value,
                            json_encode($dimensionSpacePoint, JSON_PARTIAL_OUTPUT_ON_ERROR)
                        ),
                        1667817639
                    );
                }

                $coverageNodeMoveMappings[] = CoverageNodeMoveMapping::createForNewSucceedingSibling(
                    $dimensionSpacePoint,
                    SucceedingSiblingNodeMoveDestination::create(
                        $succeedingSibling->nodeAggregateId,
                        $succeedingSibling->originDimensionSpacePoint,
                        $parentOfSucceedingSibling->nodeAggregateId,
                        $parentOfSucceedingSibling->originDimensionSpacePoint,
                    )
                );
            } else {
                // preceding / succeeding siblings could not be resolved for a given covered DSP
                // -> Fall back to resolving based on the parent

                if ($parentId === null) {
                    // if parent ID is not given, use the parent of the original node, because we want to move
                    // to the end of the sibling list.
                    $parentId = $contentGraphAdapter->findParentNodeInSubgraph($dimensionSpacePoint, $nodeAggregate->nodeAggregateId)?->nodeAggregateId;
                    if ($parentId === null) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'Parent ' . $parentId . ' not found in contentstream "%s" and dimension space point "%s" ',
                                $contentGraphAdapter->getContentStreamId()->value,
                                json_encode($dimensionSpacePoint, JSON_PARTIAL_OUTPUT_ON_ERROR)
                            ),
                            1667597013
                        );
                    }
                }
                $coverageNodeMoveMappings[] = $this->resolveNewParentAssignments(
                    $contentGraphAdapter,
                    $parentId,
                    $dimensionSpacePoint
                );
            }
        }

        return CoverageNodeMoveMappings::create(...$coverageNodeMoveMappings);
    }
}
