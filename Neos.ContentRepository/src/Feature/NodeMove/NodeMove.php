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

namespace Neos\ContentRepository\Feature\NodeMove;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateIsDescendant;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeVariantAssignment;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeVariantAssignments;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Feature\NodeMove\Command\RelationDistributionStrategy;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeMoveMapping;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeMoveMappings;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeMove
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function requireProjectedNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ContentRepository $contentRepository
    ): ReadableNodeAggregateInterface;

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
        $this->requireContentStreamToExist($command->contentStreamIdentifier, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->dimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
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

        if ($command->newParentNodeAggregateIdentifier) {
            $this->requireConstraintsImposedByAncestorsAreMet(
                $command->contentStreamIdentifier,
                $this->requireNodeType($nodeAggregate->getNodeTypeName()),
                $nodeAggregate->getNodeName(),
                [$command->newParentNodeAggregateIdentifier],
                $contentRepository
            );

            $this->requireNodeNameToBeUncovered(
                $command->contentStreamIdentifier,
                $nodeAggregate->getNodeName(),
                $command->newParentNodeAggregateIdentifier,
                $affectedDimensionSpacePoints,
                $contentRepository
            );

            $newParentNodeAggregate = $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $command->newParentNodeAggregateIdentifier,
                $contentRepository
            );

            $this->requireNodeAggregateToCoverDimensionSpacePoints(
                $newParentNodeAggregate,
                $affectedDimensionSpacePoints
            );

            $this->requireNodeAggregateToNotBeDescendant(
                $command->contentStreamIdentifier,
                $newParentNodeAggregate,
                $nodeAggregate,
                $contentRepository
            );
        }

        if ($command->newPrecedingSiblingNodeAggregateIdentifier) {
            $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $command->newPrecedingSiblingNodeAggregateIdentifier,
                $contentRepository
            );
        }
        if ($command->newSucceedingSiblingNodeAggregateIdentifier) {
            $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $command->newSucceedingSiblingNodeAggregateIdentifier,
                $contentRepository
            );
        }

        /** @var NodeVariantAssignments[] $succeedingSiblingAssignments */
        $succeedingSiblingAssignments = [];
        $parentAssignments = [];
        foreach ($nodeAggregate->getOccupiedDimensionSpacePoints() as $occupiedDimensionSpacePoint) {
            $succeedingSiblingAssignments[$occupiedDimensionSpacePoint->hash]
                = $this->resolveNewSucceedingSiblingsAssignments(
                    $command->contentStreamIdentifier,
                    $nodeAggregate,
                    $command->newParentNodeAggregateIdentifier,
                    $command->newPrecedingSiblingNodeAggregateIdentifier,
                    $command->newSucceedingSiblingNodeAggregateIdentifier,
                    $occupiedDimensionSpacePoint,
                    $affectedDimensionSpacePoints,
                    $contentRepository
                );
            $parentAssignments[$occupiedDimensionSpacePoint->hash] = $this->resolveNewParentAssignments(
                $command->contentStreamIdentifier,
                $nodeAggregate,
                $command->newParentNodeAggregateIdentifier,
                $succeedingSiblingAssignments[$occupiedDimensionSpacePoint->hash],
                $occupiedDimensionSpacePoint,
                $affectedDimensionSpacePoints,
                $contentRepository
            );
        }

        $nodeMoveMappings = $this->getNodeMoveMappings(
            $nodeAggregate,
            $parentAssignments,
            $succeedingSiblingAssignments,
            $affectedDimensionSpacePoints
        );

        $events = Events::with(
            new NodeAggregateWasMoved(
                $command->contentStreamIdentifier,
                $command->nodeAggregateIdentifier,
                $nodeMoveMappings,
                !$command->newParentNodeAggregateIdentifier
                && !$command->newSucceedingSiblingNodeAggregateIdentifier
                && !$command->newPrecedingSiblingNodeAggregateIdentifier
                    ? $affectedDimensionSpacePoints
                    : new DimensionSpacePointSet([]),
                $command->initiatingUserIdentifier
            )
        );

        $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $command->contentStreamIdentifier
        );
        ;

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
        ContentStreamIdentifier $contentStreamIdentifier,
        ReadableNodeAggregateInterface $nodeAggregate,
        /** The parent node aggregate's identifier if defined */
        ?NodeAggregateIdentifier $parentIdentifier,
        /** The already determined new succeeding siblings */
        NodeVariantAssignments $succeedingSiblingAssignments,
        /** A dimension space point occupied by the node aggregate to be moved */
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $affectedDimensionSpacePoints,
        ContentRepository $contentRepository
    ): NodeVariantAssignments {
        $parents = NodeVariantAssignments::create();
        if ($parentIdentifier) {
            // if a parent node aggregate identifier is explicitly given,
            // then all variants are assigned to it as children
            foreach (
                $nodeAggregate->getCoverageByOccupant($originDimensionSpacePoint)
                    ->getIntersection($affectedDimensionSpacePoints) as $coveredDimensionSpacePoint
            ) {
                $parents = $parents->add(
                    new NodeVariantAssignment(
                        $parentIdentifier,
                        $originDimensionSpacePoint
                    ),
                    $coveredDimensionSpacePoint
                );
            }
            return $parents;
        }

        $visibilityConstraints = VisibilityConstraints::withoutRestrictions();
        $originSubgraph = $contentRepository->getContentGraph()->getSubgraph(
            $contentStreamIdentifier,
            $originDimensionSpacePoint->toDimensionSpacePoint(),
            $visibilityConstraints
        );
        $originParent = $originSubgraph->findParentNode($nodeAggregate->getIdentifier());
        if (is_null($originParent)) {
            throw new \InvalidArgumentException(
                'Could not find parent for origin '
                . $nodeAggregate->getIdentifier()
                . ' in subgraph ' . json_encode($originSubgraph),
                1645367254
            );
        }
        foreach ($succeedingSiblingAssignments as $coveredDimensionSpacePointHash => $succeedingSiblingAssignment) {
            /** @var DimensionSpace\DimensionSpacePoint $affectedDimensionSpacePoint */
            $affectedDimensionSpacePoint = $affectedDimensionSpacePoints[$coveredDimensionSpacePointHash];
            $contentSubgraph = $contentRepository->getContentGraph()->getSubgraph(
                $contentStreamIdentifier,
                $affectedDimensionSpacePoint,
                $visibilityConstraints
            );

            $parentNode = $contentSubgraph->findParentNode($succeedingSiblingAssignment->nodeAggregateIdentifier);
            if (is_null($parentNode)) {
                throw new \InvalidArgumentException(
                    'Could not find parent for succeeding sibling '
                    . $succeedingSiblingAssignment->nodeAggregateIdentifier
                    . ' in subgraph ' . json_encode($contentSubgraph),
                    1645367254
                );
            }
            if (!$parentNode->nodeAggregateIdentifier->equals($originParent->nodeAggregateIdentifier)) {
                /** @var DimensionSpace\DimensionSpacePoint $dimensionSpacePoint */
                $dimensionSpacePoint = $affectedDimensionSpacePoints[$coveredDimensionSpacePointHash];
                $parents = $parents->add(
                    new NodeVariantAssignment(
                        $parentNode->nodeAggregateIdentifier,
                        $parentNode->originDimensionSpacePoint
                    ),
                    $dimensionSpacePoint
                );
            }
        }

        return $parents;
    }

    private function resolveAffectedDimensionSpacePointSet(
        ReadableNodeAggregateInterface $nodeAggregate,
        RelationDistributionStrategy $relationDistributionStrategy,
        DimensionSpace\DimensionSpacePoint $referenceDimensionSpacePoint
    ): DimensionSpacePointSet {
        return match ($relationDistributionStrategy) {
            RelationDistributionStrategy::STRATEGY_SCATTER =>
            new DimensionSpacePointSet([$referenceDimensionSpacePoint]),
            RelationDistributionStrategy::STRATEGY_GATHER_SPECIALIZATIONS =>
            $nodeAggregate->getCoveredDimensionSpacePoints()->getIntersection(
                $this->getInterDimensionalVariationGraph()->getSpecializationSet($referenceDimensionSpacePoint)
            ),
            default => $nodeAggregate->getCoveredDimensionSpacePoints(),
        };
    }

    /**
     * Resolves the new succeeding sibling on a per-dimension-space-point basis
     *
     * If the planned succeeding sibling does not exist in an affected dimension space point,
     * one of its siblings in the origin dimension space point is selected instead if possible.
     *
     * @todo move to content graph for more efficient calculation, if possible
     */
    private function resolveNewSucceedingSiblingsAssignments(
        /** The content stream the move operation is performed in */
        ContentStreamIdentifier $contentStreamIdentifier,
        /** The node aggregate to be moved */
        ReadableNodeAggregateInterface $nodeAggregate,
        /** The parent node aggregate identifier, has precedence over siblings when in doubt */
        ?NodeAggregateIdentifier $parentIdentifier,
        /** The planned preceding sibling's node aggregate identifier */
        ?NodeAggregateIdentifier $precedingSiblingIdentifier,
        /** The planned succeeding sibling's node aggregate identifier */
        ?NodeAggregateIdentifier $succeedingSiblingIdentifier,
        /** A dimension space point occupied by the node aggregate to be moved */
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        /** The dimension space points affected by the move operation */
        DimensionSpacePointSet $affectedDimensionSpacePoints,
        ContentRepository $contentRepository
    ): NodeVariantAssignments {
        $succeedingSiblings = NodeVariantAssignments::create();
        if (!$precedingSiblingIdentifier && !$succeedingSiblingIdentifier) {
            return $succeedingSiblings;
        }

        $visibilityConstraints = VisibilityConstraints::withoutRestrictions();
        $originContentSubgraph = $contentRepository->getContentGraph()->getSubgraph(
            $contentStreamIdentifier,
            $originDimensionSpacePoint->toDimensionSpacePoint(),
            $visibilityConstraints
        );
        foreach (
            $nodeAggregate->getCoverageByOccupant($originDimensionSpacePoint)
                ->getIntersection($affectedDimensionSpacePoints) as $dimensionSpacePoint
        ) {
            $contentSubgraph = $contentRepository->getContentGraph()->getSubgraph(
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $visibilityConstraints
            );

            $succeedingSibling = $succeedingSiblingIdentifier
                ? $this->findSibling($contentSubgraph, $parentIdentifier, $succeedingSiblingIdentifier)
                : null;
            if (!$succeedingSibling) {
                $precedingSibling = $precedingSiblingIdentifier
                    ? $this->findSibling($contentSubgraph, $parentIdentifier, $precedingSiblingIdentifier)
                    : null;
                if ($precedingSiblingIdentifier && $precedingSibling) {
                    $alternateSucceedingSiblings = $contentSubgraph->findSucceedingSiblings(
                        $precedingSiblingIdentifier,
                        null,
                        1
                    );
                    if (count($alternateSucceedingSiblings) > 0) {
                        $succeedingSibling = $alternateSucceedingSiblings->first();
                    }
                } else {
                    $succeedingSibling = $this->resolveSucceedingSiblingFromOriginSiblings(
                        $nodeAggregate->getIdentifier(),
                        $parentIdentifier,
                        $precedingSiblingIdentifier,
                        $succeedingSiblingIdentifier,
                        $contentSubgraph,
                        $originContentSubgraph
                    );
                }
            }

            if ($succeedingSibling) {
                $succeedingSiblings = $succeedingSiblings->add(
                    new NodeVariantAssignment(
                        $succeedingSibling->nodeAggregateIdentifier,
                        $succeedingSibling->originDimensionSpacePoint
                    ),
                    $dimensionSpacePoint
                );
            }
        }

        return $succeedingSiblings;
    }

    private function findSibling(
        ContentSubgraphInterface $contentSubgraph,
        ?NodeAggregateIdentifier $parentIdentifier,
        NodeAggregateIdentifier $siblingIdentifier
    ): ?Node {
        $siblingCandidate = $contentSubgraph->findNodeByNodeAggregateIdentifier($siblingIdentifier);
        if ($parentIdentifier && $siblingCandidate) {
            // If a parent node aggregate is explicitly given, all siblings must have this parent
            $parent = $contentSubgraph->findParentNode($siblingIdentifier);
            if (is_null($parent)) {
                throw new \InvalidArgumentException(
                    'Parent ' . $parentIdentifier . ' not found in subgraph ' . json_encode($contentSubgraph),
                    1645366837
                );
            }
            if ($parent->nodeAggregateIdentifier->equals($parentIdentifier)) {
                return $siblingCandidate;
            }
        } else {
            return $siblingCandidate;
        }

        return null;
    }

    private function resolveSucceedingSiblingFromOriginSiblings(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ?NodeAggregateIdentifier $parentIdentifier,
        ?NodeAggregateIdentifier $precedingSiblingIdentifier,
        ?NodeAggregateIdentifier $succeedingSiblingIdentifier,
        ContentSubgraphInterface $currentContentSubgraph,
        ContentSubgraphInterface $originContentSubgraph
    ): ?Node {
        $succeedingSibling = null;
        $precedingSiblingCandidates = iterator_to_array($precedingSiblingIdentifier
            ? $originContentSubgraph->findPrecedingSiblings($precedingSiblingIdentifier)
            : Nodes::createEmpty());
        $succeedingSiblingCandidates = iterator_to_array($succeedingSiblingIdentifier
            ? $originContentSubgraph->findSucceedingSiblings($succeedingSiblingIdentifier)
            : Nodes::createEmpty());
        $maximumIndex = max(count($succeedingSiblingCandidates), count($precedingSiblingCandidates));
        for ($i = 0; $i < $maximumIndex; $i++) {
            // try successors of same distance first
            if (isset($succeedingSiblingCandidates[$i])) {
                if ($succeedingSiblingCandidates[$i]->nodeAggregateIdentifier->equals($nodeAggregateIdentifier)) {
                    \array_splice($succeedingSiblingCandidates, $i, 1);
                }
                $succeedingSibling = $this->findSibling(
                    $currentContentSubgraph,
                    $parentIdentifier,
                    $succeedingSiblingCandidates[$i]->nodeAggregateIdentifier
                );
                if ($succeedingSibling) {
                    break;
                }
            }
            if (isset($precedingSiblingCandidates[$i])) {
                /** @var NodeAggregateIdentifier $precedingSiblingIdentifier can only be the case if not null */
                if ($precedingSiblingCandidates[$i]->nodeAggregateIdentifier->equals($nodeAggregateIdentifier)) {
                    \array_splice($precedingSiblingCandidates, $i, 1);
                }
                $precedingSibling = $this->findSibling(
                    $currentContentSubgraph,
                    $parentIdentifier,
                    $precedingSiblingCandidates[$i]->nodeAggregateIdentifier
                );
                if ($precedingSibling) {
                    $alternateSucceedingSiblings = $currentContentSubgraph->findSucceedingSiblings(
                        $precedingSiblingIdentifier,
                        null,
                        1
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

    /**
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param array|NodeVariantAssignments[] $parentAssignments
     * @param array|NodeVariantAssignments[] $succeedingSiblingAssignments
     * @param DimensionSpacePointSet|null $affectedDimensionSpacePoints
     * @return NodeMoveMappings
     */
    protected function getNodeMoveMappings(
        ReadableNodeAggregateInterface $nodeAggregate,
        array $parentAssignments,
        array $succeedingSiblingAssignments,
        ?DimensionSpacePointSet $affectedDimensionSpacePoints
    ): NodeMoveMappings {
        $nodeMoveMappings = [];
        $coveredAffectedDimensionSpacePoints = is_null($affectedDimensionSpacePoints)
            ? $nodeAggregate->getCoveredDimensionSpacePoints()
            : $nodeAggregate->getCoveredDimensionSpacePoints()->getIntersection($affectedDimensionSpacePoints);
        foreach ($coveredAffectedDimensionSpacePoints as $coveredAffectedDimensionSpacePoint) {
            $occupiedAffectedDimensionSpacePoint = $nodeAggregate->getOccupationByCovered(
                $coveredAffectedDimensionSpacePoint
            );
            $parentAssignmentsForDimensionSpacePoint = $parentAssignments[$occupiedAffectedDimensionSpacePoint->hash];
            $succeedingSiblingAssignmentsForDimensionSpacePoint
                = $succeedingSiblingAssignments[$occupiedAffectedDimensionSpacePoint->hash];
            $nodeMoveMappings[$occupiedAffectedDimensionSpacePoint->hash] = new NodeMoveMapping(
                $occupiedAffectedDimensionSpacePoint,
                $parentAssignmentsForDimensionSpacePoint,
                $succeedingSiblingAssignmentsForDimensionSpacePoint
            );
        }

        return NodeMoveMappings::fromArray($nodeMoveMappings);
    }
}
