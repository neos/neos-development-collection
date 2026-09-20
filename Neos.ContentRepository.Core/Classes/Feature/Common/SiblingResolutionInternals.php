<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * @internal implementation details of command handlers
 */
trait SiblingResolutionInternals
{
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

    public function resolveInterdimensionalSiblings(
        NodeAggregateId $nodeAggregateId,
        /** siblings resolution is an _edge_ operation, so the source does not need an origin DSP */
        DimensionSpacePoint $sourceDimensionSpacePoint,
        DimensionSpacePointSet $affectedDimensionSpacePoints,
        /** can be passed to evaluate the siblings under a new parent */
        ?NodeAggregateId $parentNodeAggregateId,
        /** can be passed to evaluate the siblings as closest to an explicit succeeding sibling */
        ?NodeAggregateId $succeedingSiblingId,
        /** can be passed to evaluate the siblings as closest to an explicit preceding sibling */
        ?NodeAggregateId $precedingSiblingId,
        ContentGraphInterface $contentGraph,
    ): InterdimensionalSiblings {
        $sourceSubgraph = $contentGraph->getSubgraph(
            $sourceDimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions(),
        );

        /** the given succeeding sibling might not cover all affected DSPs, so we fetch alternatives */
        $alternativeSucceedingSiblingIds = $succeedingSiblingId
            ? $sourceSubgraph->findSucceedingSiblingNodes(
                $succeedingSiblingId,
                FindSucceedingSiblingNodesFilter::create()
            )->toNodeAggregateIds()
            : null;

        /** the given preceding sibling might not cover all affected DSPs, so we fetch alternatives */
        $alternativePrecedingSiblingIds = $precedingSiblingId
            ? $sourceSubgraph->findPrecedingSiblingNodes(
                $precedingSiblingId,
                FindPrecedingSiblingNodesFilter::create()
            )->toNodeAggregateIds()
            : null;

        $interdimensionalSiblings = [];
        foreach ($affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $variantSubgraph = $contentGraph->getSubgraph(
                $dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions(),
            );
        }

        return new InterdimensionalSiblings(...$interdimensionalSiblings);
    }
}
