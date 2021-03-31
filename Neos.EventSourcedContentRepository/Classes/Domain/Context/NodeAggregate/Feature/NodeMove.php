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
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\MoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasMoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateIsDescendant;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeVariantAssignment;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeVariantAssignments;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\RelationDistributionStrategy;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeMoveMapping;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeMoveMappings;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeMove
{
    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function getContentGraph(): ContentGraphInterface;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function requireProjectedNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ReadableNodeAggregateInterface;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

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
            $affectedDimensionSpacePoints = $this->resolveAffectedDimensionSpacePointSet(
                $nodeAggregate,
                $command->getRelationDistributionStrategy(),
                $command->getDimensionSpacePoint()
            );

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

                $this->requireNodeAggregateToCoverDimensionSpacePoints(
                    $newParentNodeAggregate,
                    $affectedDimensionSpacePoints
                );

                $this->requireNodeAggregateToNotBeDescendant($command->getContentStreamIdentifier(), $newParentNodeAggregate, $nodeAggregate);
            }

            if ($command->getNewPrecedingSiblingNodeAggregateIdentifier()) {
                $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getNewPrecedingSiblingNodeAggregateIdentifier());
            }
            if ($command->getNewSucceedingSiblingNodeAggregateIdentifier()) {
                $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getNewSucceedingSiblingNodeAggregateIdentifier());
            }

            /** @var NodeVariantAssignments[] $succeedingSiblingAssignments */
            $succeedingSiblingAssignments = [];
            $parentAssignments = [];
            foreach ($nodeAggregate->getOccupiedDimensionSpacePoints() as $occupiedDimensionSpacePoint) {
                $succeedingSiblingAssignments[$occupiedDimensionSpacePoint->getHash()] = $this->resolveNewSucceedingSiblingsAssignments(
                    $command->getContentStreamIdentifier(),
                    $nodeAggregate,
                    $command->getNewParentNodeAggregateIdentifier(),
                    $command->getNewPrecedingSiblingNodeAggregateIdentifier(),
                    $command->getNewSucceedingSiblingNodeAggregateIdentifier(),
                    $occupiedDimensionSpacePoint,
                    $affectedDimensionSpacePoints
                );
                $parentAssignments[$occupiedDimensionSpacePoint->getHash()] = $this->resolveNewParentAssignments(
                    $command->getContentStreamIdentifier(),
                    $nodeAggregate,
                    $command->getNewParentNodeAggregateIdentifier(),
                    $succeedingSiblingAssignments[$occupiedDimensionSpacePoint->getHash()],
                    $occupiedDimensionSpacePoint,
                    $affectedDimensionSpacePoints
                );
            }

            $nodeMoveMappings = $this->getNodeMoveMappings($nodeAggregate, $parentAssignments, $succeedingSiblingAssignments, $affectedDimensionSpacePoints);

            $events = DomainEvents::withSingleEvent(
                DecoratedEvent::addIdentifier(
                    new NodeAggregateWasMoved(
                        $command->getContentStreamIdentifier(),
                        $command->getNodeAggregateIdentifier(),
                        $nodeMoveMappings,
                        !$command->getNewParentNodeAggregateIdentifier() && !$command->getNewSucceedingSiblingNodeAggregateIdentifier() && !$command->getNewPrecedingSiblingNodeAggregateIdentifier() ? $affectedDimensionSpacePoints : new DimensionSpacePointSet([]),
                        $command->getInitiatingUserIdentifier()
                    ),
                    Uuid::uuid4()->toString()
                )
            );

            $contentStreamEventStreamName = ContentStream\ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier());
            $this->getNodeAggregateEventPublisher()->publishMany(
                $contentStreamEventStreamName->getEventStreamName(),
                $events
            );
        });

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }

    /**
     * Resolves the new parents on a per-dimension-space-point basis
     *
     * If no parent node aggregate is defined, it will be resolved from the already evaluated new succeeding siblings.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier The content stream the move operation is performed in
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param NodeAggregateIdentifier|null $parentIdentifier The parent node aggregate's identifier if defined
     * @param NodeVariantAssignments $succeedingSiblingAssignments The already determined new succeeding siblings
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint A dimension space point occupied by the node aggregate to be moved
     * @param DimensionSpacePointSet $affectedDimensionSpacePoints
     * @return NodeVariantAssignments
     * @todo move to content graph for more efficient calculation, if possible
     */
    private function resolveNewParentAssignments(
        ContentStreamIdentifier $contentStreamIdentifier,
        ReadableNodeAggregateInterface $nodeAggregate,
        ?NodeAggregateIdentifier $parentIdentifier,
        NodeVariantAssignments $succeedingSiblingAssignments,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): NodeVariantAssignments {
        $parents = NodeVariantAssignments::create();
        if ($parentIdentifier) {
            // if a parent node aggregate identifier is explicitly given, then all variants are assigned to it as children
            foreach ($nodeAggregate->getCoverageByOccupant($originDimensionSpacePoint)->getIntersection($affectedDimensionSpacePoints) as $coveredDimensionSpacePoint) {
                $parents = $parents->add(new NodeVariantAssignment($parentIdentifier, $originDimensionSpacePoint), $coveredDimensionSpacePoint);
            }
            return $parents;
        }

        $visibilityConstraints = VisibilityConstraints::withoutRestrictions();
        $originSubgraph = $this->getContentGraph()->getSubgraphByIdentifier(
            $contentStreamIdentifier,
            $originDimensionSpacePoint,
            $visibilityConstraints
        );
        $originParent = $originSubgraph->findParentNode($nodeAggregate->getIdentifier());
        foreach ($succeedingSiblingAssignments as $coveredDimensionSpacePointHash => $succeedingSiblingAssignment) {
            $contentSubgraph = $this->getContentGraph()->getSubgraphByIdentifier(
                $contentStreamIdentifier,
                $affectedDimensionSpacePoints[$coveredDimensionSpacePointHash],
                $visibilityConstraints
            );

            $parentNode = $contentSubgraph->findParentNode($succeedingSiblingAssignment->getNodeAggregateIdentifier());
            if (!$parentNode->getNodeAggregateIdentifier()->equals($originParent->getNodeAggregateIdentifier())) {
                $parents = $parents->add(
                    new NodeVariantAssignment(
                        $parentNode->getNodeAggregateIdentifier(),
                        $parentNode->getOriginDimensionSpacePoint()
                    ),
                    $affectedDimensionSpacePoints[$coveredDimensionSpacePointHash]
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
        switch ($relationDistributionStrategy->getStrategy()) {
            case RelationDistributionStrategy::STRATEGY_SCATTER:
                return new DimensionSpacePointSet([$referenceDimensionSpacePoint]);
                break;
            case RelationDistributionStrategy::STRATEGY_GATHER_SPECIALIZATIONS:
                return $nodeAggregate->getCoveredDimensionSpacePoints()->getIntersection(
                    $this->getInterDimensionalVariationGraph()->getSpecializationSet($referenceDimensionSpacePoint)
                );
                break;
            case RelationDistributionStrategy::STRATEGY_GATHER_ALL:
            default:
                return $nodeAggregate->getCoveredDimensionSpacePoints();
        }
    }

    /**
     * Resolves the new succeeding sibling on a per-dimension-space-point basis
     *
     * If the planned succeeding sibling does not exist in an affected dimension space point,
     * one of its siblings in the origin dimension space point is selected instead if possible.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier The content stream the move operation is performed in
     * @param ReadableNodeAggregateInterface $nodeAggregate The node aggregate to be moved
     * @param NodeAggregateIdentifier|null $parentIdentifier The parent node aggregate identifier, has precedence over siblings when in doubt
     * @param NodeAggregateIdentifier|null $precedingSiblingIdentifier The planned preceding sibling's node aggregate identifier
     * @param NodeAggregateIdentifier|null $succeedingSiblingIdentifier The planned succeeding sibling's node aggregate identifier
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint A dimension space point occupied by the node aggregate to be moved
     * @param DimensionSpacePointSet $affectedDimensionSpacePoints The dimension space points affected by the move operation
     * @return NodeVariantAssignments
     * @todo move to content graph for more efficient calculation, if possible
     */
    private function resolveNewSucceedingSiblingsAssignments(
        ContentStreamIdentifier $contentStreamIdentifier,
        ReadableNodeAggregateInterface $nodeAggregate,
        ?NodeAggregateIdentifier $parentIdentifier,
        ?NodeAggregateIdentifier $precedingSiblingIdentifier,
        ?NodeAggregateIdentifier $succeedingSiblingIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): NodeVariantAssignments {
        $succeedingSiblings = NodeVariantAssignments::create();
        if (!$precedingSiblingIdentifier && !$succeedingSiblingIdentifier) {
            return $succeedingSiblings;
        }

        $visibilityConstraints = VisibilityConstraints::withoutRestrictions();
        $originContentSubgraph = $this->getContentGraph()->getSubgraphByIdentifier($contentStreamIdentifier, $originDimensionSpacePoint, $visibilityConstraints);
        foreach ($nodeAggregate->getCoverageByOccupant($originDimensionSpacePoint)->getIntersection($affectedDimensionSpacePoints) as $dimensionSpacePoint) {
            $contentSubgraph = $this->getContentGraph()->getSubgraphByIdentifier($contentStreamIdentifier, $dimensionSpacePoint, $visibilityConstraints);

            $succeedingSibling = $succeedingSiblingIdentifier ? $this->findSibling($contentSubgraph, $parentIdentifier, $succeedingSiblingIdentifier) : null;
            if (!$succeedingSibling) {
                $precedingSibling = $precedingSiblingIdentifier ? $this->findSibling($contentSubgraph, $parentIdentifier, $precedingSiblingIdentifier) : null;
                if ($precedingSibling) {
                    $alternateSucceedingSiblings = $contentSubgraph->findSucceedingSiblings($precedingSiblingIdentifier, null, 1);
                    if (count($alternateSucceedingSiblings) > 0) {
                        $succeedingSibling = reset($alternateSucceedingSiblings);
                    }
                } else {
                    $succeedingSibling = $this->resolveSucceedingSiblingFromOriginSiblings($nodeAggregate->getIdentifier(), $parentIdentifier, $precedingSiblingIdentifier, $succeedingSiblingIdentifier, $contentSubgraph, $originContentSubgraph);
                }
            }

            if ($succeedingSibling) {
                $succeedingSiblings = $succeedingSiblings->add(
                    new NodeVariantAssignment(
                        $succeedingSibling->getNodeAggregateIdentifier(),
                        $succeedingSibling->getOriginDimensionSpacePoint()
                    ),
                    $dimensionSpacePoint
                );
            }
        }

        return $succeedingSiblings;
    }

    private function findSibling(ContentSubgraphInterface $contentSubgraph, ?NodeAggregateIdentifier $parentIdentifier, NodeAggregateIdentifier $siblingIdentifier): ?NodeInterface
    {
        $siblingCandidate = $contentSubgraph->findNodeByNodeAggregateIdentifier($siblingIdentifier);
        if ($parentIdentifier && $siblingCandidate) {
            // If a parent node aggregate is explicitly given, all siblings must have this parent
            $parent = $contentSubgraph->findParentNode($siblingIdentifier);
            if ($parent->getNodeAggregateIdentifier()->equals($parentIdentifier)) {
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
    ): ?NodeInterface {
        $succeedingSibling = null;
        $precedingSiblingCandidates = $precedingSiblingIdentifier ? $originContentSubgraph->findPrecedingSiblings($precedingSiblingIdentifier): [];
        $succeedingSiblingCandidates = $succeedingSiblingIdentifier ? $originContentSubgraph->findSucceedingSiblings($succeedingSiblingIdentifier) : [];
        $maximumIndex = max(count($succeedingSiblingCandidates), count($precedingSiblingCandidates));
        for ($i = 0; $i < $maximumIndex; $i++) {
            // try successors of same distance first
            if (isset($succeedingSiblingCandidates[$i])) {
                if ($succeedingSiblingCandidates[$i]->getNodeAggregateIdentifier()->equals($nodeAggregateIdentifier)) {
                    \array_splice($succeedingSiblingCandidates, $i, 1);
                }
                $succeedingSibling = $this->findSibling($currentContentSubgraph, $parentIdentifier, $succeedingSiblingCandidates[$i]->getNodeAggregateIdentifier());
                if ($succeedingSibling) {
                    break;
                }
            }
            if (isset($precedingSiblingCandidates[$i])) {
                if ($precedingSiblingCandidates[$i]->getNodeAggregateIdentifier()->equals($nodeAggregateIdentifier)) {
                    \array_splice($precedingSiblingCandidates, $i, 1);
                }
                $precedingSibling = $this->findSibling($currentContentSubgraph, $parentIdentifier, $precedingSiblingCandidates[$i]->getNodeAggregateIdentifier());
                if ($precedingSibling) {
                    $alternateSucceedingSiblings = $currentContentSubgraph->findSucceedingSiblings($precedingSiblingIdentifier, null, 1);
                    if (count($alternateSucceedingSiblings) > 0) {
                        $succeedingSibling = reset($alternateSucceedingSiblings);
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
        foreach ($nodeAggregate->getCoveredDimensionSpacePoints()->getIntersection($affectedDimensionSpacePoints) as $coveredAffectedDimensionSpacePoint) {
            $occupiedAffectedDimensionSpacePoint = $nodeAggregate->getOccupationByCovered($coveredAffectedDimensionSpacePoint);
            $parentAssignmentsForDimensionSpacePoint = $parentAssignments[$occupiedAffectedDimensionSpacePoint->getHash()];
            $succeedingSiblingAssignmentsForDimensionSpacePoint = $succeedingSiblingAssignments[$occupiedAffectedDimensionSpacePoint->getHash()];
            $nodeMoveMappings[$occupiedAffectedDimensionSpacePoint->getHash()] = new NodeMoveMapping(
                $occupiedAffectedDimensionSpacePoint,
                $parentAssignmentsForDimensionSpacePoint,
                $succeedingSiblingAssignmentsForDimensionSpacePoint
            );
        }

        return NodeMoveMappings::fromArray($nodeMoveMappings);
    }
}
