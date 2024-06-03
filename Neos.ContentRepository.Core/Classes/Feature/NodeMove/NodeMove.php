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

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSibling;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
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
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
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
     * @throws NodeAggregatesTypeIsAmbiguous
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
                $command->workspaceName,
                $contentStreamId,
                $command->nodeAggregateId,
                $command->newParentNodeAggregateId,
                $this->resolveInterdimensionalSiblingsForMove(
                    $contentGraph,
                    $command->dimensionSpacePoint,
                    $affectedDimensionSpacePoints,
                    $command->nodeAggregateId,
                    $command->newParentNodeAggregateId,
                    $command->newSucceedingSiblingNodeAggregateId,
                    $command->newPrecedingSiblingNodeAggregateId,
                    ($command->newParentNodeAggregateId !== null)
                        || (($command->newSucceedingSiblingNodeAggregateId === null) && ($command->newPrecedingSiblingNodeAggregateId === null)),
                )
            )
        );

        $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamId(
            $contentStreamId
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

    /**
     * @param ?NodeAggregateId $parentNodeAggregateId the parent node aggregate ID to validate variant siblings against.
     *      If no new parent is given, the siblings are validated against the parent of the to-be-moved node in the respective dimension space point.
     * @param bool $completeSet Whether unresolvable siblings should be added as null or not at all
     *                          True when a new parent is set, which will result of the node being added at the end
     *                          True when no preceding sibling is given and the succeeding sibling is explicitly set to null, which will result of the node being added at the end
     *                          False when no new parent is set, which will result in the node not being moved
     */
    private function resolveInterdimensionalSiblingsForMove(
        ContentGraphInterface $contentGraph,
        DimensionSpacePoint $selectedDimensionSpacePoint,
        DimensionSpacePointSet $affectedDimensionSpacePoints,
        NodeAggregateId $nodeAggregateId,
        ?NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingId,
        ?NodeAggregateId $precedingSiblingId,
        bool $completeSet,
    ): InterdimensionalSiblings {
        $selectedSubgraph = $contentGraph->getSubgraph(
            $selectedDimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $alternativeSucceedingSiblingIds = $succeedingSiblingId
            ? $selectedSubgraph->findSucceedingSiblingNodes(
                $succeedingSiblingId,
                FindSucceedingSiblingNodesFilter::create()
            )->toNodeAggregateIds()
            : null;
        $alternativePrecedingSiblingIds = $precedingSiblingId
            ? $selectedSubgraph->findPrecedingSiblingNodes(
                $precedingSiblingId,
                FindPrecedingSiblingNodesFilter::create()
            )->toNodeAggregateIds()
            : null;

        $interdimensionalSiblings = [];
        foreach ($affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $variantSubgraph = $contentGraph->getSubgraph(
                $dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );
            if ($succeedingSiblingId) {
                $variantSucceedingSibling = $variantSubgraph->findNodeById($succeedingSiblingId);
                $variantParentId = $parentNodeAggregateId ?: $variantSubgraph->findParentNode($nodeAggregateId)?->aggregateId;
                $siblingParent = $variantSubgraph->findParentNode($succeedingSiblingId);
                if ($variantSucceedingSibling && $siblingParent && $variantParentId?->equals($siblingParent->aggregateId)) {
                    // a) happy path, the explicitly requested succeeding sibling also exists in this dimension space point
                    $interdimensionalSiblings[] = new InterdimensionalSibling(
                        $dimensionSpacePoint,
                        $variantSucceedingSibling->aggregateId,
                    );
                    continue;
                }

                // check the other siblings succeeding in the selected dimension space point
                foreach ($alternativeSucceedingSiblingIds ?: [] as $alternativeSucceedingSiblingId) {
                    // the node itself is no valid succeeding sibling
                    if ($alternativeSucceedingSiblingId->equals($nodeAggregateId)) {
                        continue;
                    }
                    $alternativeVariantSucceedingSibling = $variantSubgraph->findNodeById($alternativeSucceedingSiblingId);
                    if (!$alternativeVariantSucceedingSibling) {
                        continue;
                    }
                    $siblingParent = $variantSubgraph->findParentNode($alternativeSucceedingSiblingId);
                    if (!$siblingParent || !$variantParentId?->equals($siblingParent->aggregateId)) {
                        continue;
                    }
                    // b) one of the further succeeding sibling exists in this dimension space point
                    $interdimensionalSiblings[] = new InterdimensionalSibling(
                        $dimensionSpacePoint,
                        $alternativeVariantSucceedingSibling->aggregateId,
                    );
                    continue 2;
                }
            }

            if ($precedingSiblingId) {
                $variantPrecedingSiblingId = null;
                $variantPrecedingSibling = $variantSubgraph->findNodeById($precedingSiblingId);
                $variantParentId = $parentNodeAggregateId ?: $variantSubgraph->findParentNode($nodeAggregateId)?->aggregateId;
                $siblingParent = $variantSubgraph->findParentNode($precedingSiblingId);
                if ($variantPrecedingSibling && $siblingParent && $variantParentId?->equals($siblingParent->aggregateId)) {
                    // c) happy path, the explicitly requested preceding sibling also exists in this dimension space point
                    $variantPrecedingSiblingId = $precedingSiblingId;
                } elseif ($alternativePrecedingSiblingIds) {
                    // check the other siblings preceding in the selected dimension space point
                    foreach ($alternativePrecedingSiblingIds as $alternativePrecedingSiblingId) {
                        // the node itself is no valid preceding sibling
                        if ($alternativePrecedingSiblingId->equals($nodeAggregateId)) {
                            continue;
                        }
                        $siblingParent = $variantSubgraph->findParentNode($alternativePrecedingSiblingId);
                        if (!$siblingParent || !$variantParentId?->equals($siblingParent->aggregateId)) {
                            continue;
                        }
                        $alternativeVariantSucceedingSibling = $variantSubgraph->findNodeById($alternativePrecedingSiblingId);
                        if ($alternativeVariantSucceedingSibling) {
                            // d) one of the further preceding siblings exists in this dimension space point
                            $variantPrecedingSiblingId = $alternativePrecedingSiblingId;
                            break;
                        }
                    }
                }

                if ($variantPrecedingSiblingId) {
                    // we fetch two siblings because the first might be the to-be-moved node itself
                    $variantSucceedingSiblingIds = $variantSubgraph->findSucceedingSiblingNodes(
                        $variantPrecedingSiblingId,
                        FindSucceedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(2, 0))
                    )->toNodeAggregateIds();
                    $relevantVariantSucceedingSiblingId = null;
                    foreach ($variantSucceedingSiblingIds as $variantSucceedingSiblingId) {
                        if (!$variantSucceedingSiblingId->equals($nodeAggregateId)) {
                            $relevantVariantSucceedingSiblingId = $variantSucceedingSiblingId;
                            break;
                        }
                    }
                    $interdimensionalSiblings[] = new InterdimensionalSibling(
                        $dimensionSpacePoint,
                        $relevantVariantSucceedingSiblingId,
                    );
                    continue;
                }
            }

            // e) fallback: if the set is to be completed, we add an empty sibling, otherwise we just don't
            if ($completeSet) {
                $interdimensionalSiblings[] = new InterdimensionalSibling(
                    $dimensionSpacePoint,
                    null,
                );
            }
        }

        return new InterdimensionalSiblings(...$interdimensionalSiblings);
    }
}
