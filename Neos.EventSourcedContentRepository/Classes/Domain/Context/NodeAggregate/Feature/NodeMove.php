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
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
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

    abstract protected function getContentGraph(): ContentGraphInterface;

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

            $newPrecedingSiblingNodeAggregate = null;
            if ($command->getNewPrecedingSiblingNodeAggregateIdentifier()) {
                $newPrecedingSiblingNodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getNewPrecedingSiblingNodeAggregateIdentifier());
                if (!$command->getNewParentNodeAggregateIdentifier()) {
                    $this->requireNodeAggregateToBeSibling($command->getContentStreamIdentifier(), $newPrecedingSiblingNodeAggregate, $nodeAggregate);
                }
            }
            $newSucceedingSiblingNodeAggregate = null;
            if ($command->getNewSucceedingSiblingNodeAggregateIdentifier()) {
                $newSucceedingSiblingNodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getNewSucceedingSiblingNodeAggregateIdentifier());
                if (!$command->getNewParentNodeAggregateIdentifier()) {
                    $this->requireNodeAggregateToBeSibling($command->getContentStreamIdentifier(), $newSucceedingSiblingNodeAggregate, $nodeAggregate);
                }
            }

            $succeedingSiblingAssignments = [];
            foreach ($nodeAggregate->getOccupiedDimensionSpacePoints() as $occupiedDimensionSpacePoint) {
                $succeedingSiblingAssignments[$occupiedDimensionSpacePoint->getHash()] = $this->resolveNewSucceedingSiblingsAssignments(
                    $command->getContentStreamIdentifier(),
                    $nodeAggregate,
                    $command->getNewPrecedingSiblingNodeAggregateIdentifier(),
                    $command->getNewSucceedingSiblingNodeAggregateIdentifier(),
                    $occupiedDimensionSpacePoint,
                    $affectedDimensionSpacePoints
                );
            }

            $nodeMoveMappings = $this->getNodeMoveMappings($nodeAggregate, $newParentNodeAggregate, $succeedingSiblingAssignments, $affectedDimensionSpacePoints);

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

    /**
     * Resolves the new succeeding sibling on a per-dimension-space-point basis
     *
     * If the planned succeeding sibling does not exist in an affected dimension space point,
     * one of its siblings in the origin dimension space point is selected instead if possible.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier The content stream the move operation is performed in
     * @param ReadableNodeAggregateInterface $nodeAggregate The node aggregate to be moved
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

            $succeedingSibling = $succeedingSiblingIdentifier ? $contentSubgraph->findNodeByNodeAggregateIdentifier($succeedingSiblingIdentifier) : null;
            if (!$succeedingSibling) {
                $precedingSibling = $precedingSiblingIdentifier ? $contentSubgraph->findNodeByNodeAggregateIdentifier($precedingSiblingIdentifier) : null;
                if ($precedingSibling) {
                    $alternateSucceedingSiblings = $contentSubgraph->findSucceedingSiblings($precedingSiblingIdentifier, null, 1);
                    if (count($alternateSucceedingSiblings) > 0) {
                        $succeedingSibling = reset($alternateSucceedingSiblings);
                    }
                } else {
                    $succeedingSibling = $this->resolveSucceedingSiblingFromOriginSiblings($nodeAggregate->getIdentifier(), $precedingSiblingIdentifier, $succeedingSiblingIdentifier, $contentSubgraph, $originContentSubgraph);
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

    private function resolveSucceedingSiblingFromOriginSiblings(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
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
                $succeedingSibling = $currentContentSubgraph->findNodeByNodeAggregateIdentifier($succeedingSiblingCandidates[$i]->getNodeAggregateIdentifier());
                if ($succeedingSibling) {
                    break;
                }
            }
            if (isset($precedingSiblingCandidates[$i])) {
                if ($precedingSiblingCandidates[$i]->getNodeAggregateIdentifier()->equals($nodeAggregateIdentifier)) {
                    \array_splice($precedingSiblingCandidates, $i, 1);
                }
                $precedingSibling = $currentContentSubgraph->findNodeByNodeAggregateIdentifier($precedingSiblingCandidates[$i]->getNodeAggregateIdentifier());
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
     * @param ReadableNodeAggregateInterface|null $newParentNodeAggregate
     * @param array|NodeVariantAssignments[] $succeedingSiblingAssignments
     * @param DimensionSpacePointSet|null $affectedDimensionSpacePoints
     * @return NodeMoveMappings
     */
    protected function getNodeMoveMappings(
        ReadableNodeAggregateInterface $nodeAggregate,
        ?ReadableNodeAggregateInterface $newParentNodeAggregate,
        array $succeedingSiblingAssignments,
        ?DimensionSpacePointSet $affectedDimensionSpacePoints
    ): NodeMoveMappings {
        $nodeMoveMappings = [];
        foreach ($nodeAggregate->getOccupiedDimensionSpacePoints()->getIntersection(OriginDimensionSpacePointSet::fromDimensionSpacePointSet($affectedDimensionSpacePoints)) as $occupiedAffectedDimensionSpacePoint) {
            $succeedingSiblingAssignmentsForDimensionSpacePoint = $succeedingSiblingAssignments[$occupiedAffectedDimensionSpacePoint->getHash()];
            $nodeMoveMappings[] = new NodeMoveMapping(
                $occupiedAffectedDimensionSpacePoint,
                $newParentNodeAggregate
                    ? $newParentNodeAggregate->getOccupationByCovered($occupiedAffectedDimensionSpacePoint)
                    : null,
                $succeedingSiblingAssignmentsForDimensionSpacePoint,
                $newParentNodeAggregate
                    ? $newParentNodeAggregate->getCoverageByOccupant($newParentNodeAggregate->getOccupationByCovered($occupiedAffectedDimensionSpacePoint))->getIntersection($affectedDimensionSpacePoints)
                    : null
            );
        }

        return NodeMoveMappings::fromArray($nodeMoveMappings);
    }
}
