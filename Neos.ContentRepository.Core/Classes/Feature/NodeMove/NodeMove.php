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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMappings;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\ParentNodeMoveDestination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsDescendant;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\SucceedingSiblingNodeMoveDestination;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\RelationDistributionStrategy;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMappings;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeMove
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function requireProjectedNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        ContentRepository $contentRepository
    ): NodeAggregate;

    /**
     * @param MoveNodeAggregate $command
     * @return EventsToPublish
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws NodeAggregateCurrentlyDoesNotExist
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregateIsDescendant
     */
    private function handleMoveNodeAggregate(
        MoveNodeAggregate $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->dimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
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
                $command->contentStreamId,
                $this->requireNodeType($nodeAggregate->nodeTypeName),
                $nodeAggregate->nodeName,
                [$command->newParentNodeAggregateId],
                $contentRepository
            );

            $this->requireNodeNameToBeUncovered(
                $command->contentStreamId,
                $nodeAggregate->nodeName,
                $command->newParentNodeAggregateId,
                $affectedDimensionSpacePoints,
                $contentRepository
            );

            $newParentNodeAggregate = $this->requireProjectedNodeAggregate(
                $command->contentStreamId,
                $command->newParentNodeAggregateId,
                $contentRepository
            );

            $this->requireNodeAggregateToCoverDimensionSpacePoints(
                $newParentNodeAggregate,
                $affectedDimensionSpacePoints
            );

            $this->requireNodeAggregateToNotBeDescendant(
                $command->contentStreamId,
                $newParentNodeAggregate,
                $nodeAggregate,
                $contentRepository
            );
        }

        if ($command->newPrecedingSiblingNodeAggregateId) {
            $this->requireProjectedNodeAggregate(
                $command->contentStreamId,
                $command->newPrecedingSiblingNodeAggregateId,
                $contentRepository
            );
        }
        if ($command->newSucceedingSiblingNodeAggregateId) {
            $this->requireProjectedNodeAggregate(
                $command->contentStreamId,
                $command->newSucceedingSiblingNodeAggregateId,
                $contentRepository
            );
        }

        /** @var OriginNodeMoveMapping[] $originNodeMoveMappings */
        $originNodeMoveMappings = [];
        foreach ($nodeAggregate->occupiedDimensionSpacePoints as $movedNodeOrigin) {
            $originNodeMoveMappings[] = new OriginNodeMoveMapping(
                $movedNodeOrigin,
                $this->resolveCoverageNodeMoveMappings(
                    $command->contentStreamId,
                    $nodeAggregate,
                    $command->newParentNodeAggregateId,
                    $command->newPrecedingSiblingNodeAggregateId,
                    $command->newSucceedingSiblingNodeAggregateId,
                    $movedNodeOrigin,
                    $affectedDimensionSpacePoints,
                    $contentRepository
                )
            );
        }

        $events = Events::with(
            new NodeAggregateWasMoved(
                $command->contentStreamId,
                $command->nodeAggregateId,
                OriginNodeMoveMappings::create(...$originNodeMoveMappings)
            )
        );

        $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamId(
            $command->contentStreamId
        );

        return new EventsToPublish(
            $contentStreamEventStreamName->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            ExpectedVersion::ANY()
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
        /** The content stream the move operation is performed in */
        ContentStreamId $contentStreamId,
        /** The parent node aggregate's id*/
        NodeAggregateId $parentId,
        DimensionSpace\DimensionSpacePoint $coveredDimensionSpacePoint,
        ContentRepository $contentRepository
    ): CoverageNodeMoveMapping {
        $contentSubgraph = $contentRepository->getContentGraph()->getSubgraph(
            $contentStreamId,
            $coveredDimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $parentNode = $contentSubgraph->findNodeById($parentId);
        if ($parentNode === null) {
            throw new \InvalidArgumentException(
                'Parent ' . $parentId . ' not found in subgraph ' . json_encode($contentSubgraph),
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

    private function findSibling(
        ContentSubgraphInterface $contentSubgraph,
        ?NodeAggregateId $parentId,
        NodeAggregateId $siblingId
    ): ?Node {
        $siblingCandidate = $contentSubgraph->findNodeById($siblingId);
        if ($parentId && $siblingCandidate) {
            // If a parent node aggregate is explicitly given, all siblings must have this parent
            $parent = $contentSubgraph->findParentNode($siblingId);
            if (is_null($parent)) {
                throw new \InvalidArgumentException(
                    'Parent ' . $parentId . ' not found in subgraph ' . json_encode($contentSubgraph),
                    1645366837
                );
            }
            if ($parent->nodeAggregateId->equals($parentId)) {
                return $siblingCandidate;
            }
        } else {
            return $siblingCandidate;
        }

        return null;
    }

    private function resolveSucceedingSiblingFromOriginSiblings(
        NodeAggregateId $nodeAggregateId,
        ?NodeAggregateId $parentId,
        ?NodeAggregateId $precedingSiblingId,
        ?NodeAggregateId $succeedingSiblingId,
        ContentSubgraphInterface $currentContentSubgraph,
        ContentSubgraphInterface $originContentSubgraph
    ): ?Node {
        $succeedingSibling = null;
        $precedingSiblingCandidates = iterator_to_array(
            $precedingSiblingId
                ? $originContentSubgraph->findPrecedingSiblingNodes($precedingSiblingId, FindPrecedingSiblingNodesFilter::create())
                : Nodes::createEmpty()
        );
        $succeedingSiblingCandidates = iterator_to_array(
            $succeedingSiblingId
                ? $originContentSubgraph->findSucceedingSiblingNodes(
                    $succeedingSiblingId,
                    FindSucceedingSiblingNodesFilter::create()
                )
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
                $succeedingSibling = $this->findSibling(
                    $currentContentSubgraph,
                    $parentId,
                    $succeedingSiblingCandidates[$i]->nodeAggregateId
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
                $precedingSibling = $this->findSibling(
                    $currentContentSubgraph,
                    $parentId,
                    $precedingSiblingCandidates[$i]->nodeAggregateId
                );
                if ($precedingSibling) {
                    $alternateSucceedingSiblings = $currentContentSubgraph->findSucceedingSiblingNodes(
                        $precedingSiblingId,
                        FindSucceedingSiblingNodesFilter::create()->withPagination(1, 0)
                    );
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
        /** The content stream the move operation is performed in */
        ContentStreamId $contentStreamId,
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
        DimensionSpacePointSet $affectedDimensionSpacePoints,
        ContentRepository $contentRepository
    ): CoverageNodeMoveMappings {
        /** @var CoverageNodeMoveMapping[] $coverageNodeMoveMappings */
        $coverageNodeMoveMappings = [];

        $visibilityConstraints = VisibilityConstraints::withoutRestrictions();
        $originContentSubgraph = $contentRepository->getContentGraph()->getSubgraph(
            $contentStreamId,
            $originDimensionSpacePoint->toDimensionSpacePoint(),
            $visibilityConstraints
        );
        foreach (
            $nodeAggregate->getCoverageByOccupant($originDimensionSpacePoint)
                ->getIntersection($affectedDimensionSpacePoints) as $dimensionSpacePoint
        ) {
            $contentSubgraph = $contentRepository->getContentGraph()->getSubgraph(
                $contentStreamId,
                $dimensionSpacePoint,
                $visibilityConstraints
            );

            $succeedingSibling = $succeedingSiblingId
                ? $this->findSibling($contentSubgraph, $parentId, $succeedingSiblingId)
                : null;
            if (!$succeedingSibling) {
                $precedingSibling = $precedingSiblingId
                    ? $this->findSibling($contentSubgraph, $parentId, $precedingSiblingId)
                    : null;
                if ($precedingSiblingId && $precedingSibling) {
                    $alternateSucceedingSiblings = $contentSubgraph->findSucceedingSiblingNodes(
                        $precedingSiblingId,
                        FindSucceedingSiblingNodesFilter::create()->withPagination(1, 0)
                    );
                    if (count($alternateSucceedingSiblings) > 0) {
                        $succeedingSibling = $alternateSucceedingSiblings->first();
                    }
                } else {
                    $succeedingSibling = $this->resolveSucceedingSiblingFromOriginSiblings(
                        $nodeAggregate->nodeAggregateId,
                        $parentId,
                        $precedingSiblingId,
                        $succeedingSiblingId,
                        $contentSubgraph,
                        $originContentSubgraph
                    );
                }
            }

            if ($succeedingSibling) {
                // for the event payload, we additionally need the parent of the succeeding sibling
                $parentOfSucceedingSibling = $contentSubgraph->findParentNode($succeedingSibling->nodeAggregateId);
                if ($parentOfSucceedingSibling === null) {
                    throw new \InvalidArgumentException(
                        'Parent of succeeding sibling ' . $succeedingSibling->nodeAggregateId
                        . ' not found in subgraph ' . json_encode($contentSubgraph),
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
                    $parentId = $contentSubgraph->findParentNode($nodeAggregate->nodeAggregateId)?->nodeAggregateId;
                    if ($parentId === null) {
                        throw new \InvalidArgumentException(
                            'Parent ' . $parentId . ' not found in subgraph ' . json_encode($contentSubgraph),
                            1667597013
                        );
                    }
                }
                $coverageNodeMoveMappings[] = $this->resolveNewParentAssignments(
                    $contentStreamId,
                    $parentId,
                    $dimensionSpacePoint,
                    $contentRepository
                );
            }
        }

        return CoverageNodeMoveMappings::create(...$coverageNodeMoveMappings);
    }
}
