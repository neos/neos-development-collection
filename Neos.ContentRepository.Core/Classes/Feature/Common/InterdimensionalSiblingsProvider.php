<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * @internal implementation details of command handlers
 * @todo this could be part of some projection content graph interface to reduce database I/O
 */
trait InterdimensionalSiblingsProvider
{
    /**
     * Resolves the succeeding siblings for the given node at the given target in the given dimension space points
     */
    protected function resolveInterdimensionalSiblings(
        ContentGraphInterface $contentGraph,
        DimensionSpacePoint $selectedDimensionSpacePoint,
        DimensionSpacePointSet $affectedDimensionSpacePoints,
        NodeAggregateId $nodeAggregateId,
        ?NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        bool $completeSet,
    ): InterdimensionalSiblings {
        $selectedSubgraph = $contentGraph->getSubgraph(
            $selectedDimensionSpacePoint,
            VisibilityConstraints::createEmpty()
        );
        $alternativeSucceedingSiblingIds = $succeedingSiblingNodeAggregateId
            ? $selectedSubgraph->findSucceedingSiblingNodes(
                $succeedingSiblingNodeAggregateId,
                FindSucceedingSiblingNodesFilter::create()
            )->toNodeAggregateIds()
            : null;

        $alternativePrecedingSiblingIds = $precedingSiblingNodeAggregateId
            ? $selectedSubgraph->findPrecedingSiblingNodes(
                $precedingSiblingNodeAggregateId,
                FindPrecedingSiblingNodesFilter::create()
            )->toNodeAggregateIds()
            : null;

        $interdimensionalSiblings = [];
        foreach ($affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $variantSubgraph = $contentGraph->getSubgraph(
                $dimensionSpacePoint,
                VisibilityConstraints::createEmpty()
            );
            if ($succeedingSiblingNodeAggregateId) {
                $variantSucceedingSibling = $variantSubgraph->findNodeById($succeedingSiblingNodeAggregateId);
                $variantParentId = $parentNodeAggregateId ?: $variantSubgraph->findParentNode($nodeAggregateId)?->aggregateId;
                $siblingParent = $variantSubgraph->findParentNode($succeedingSiblingNodeAggregateId);
                \Neos\Flow\var_dump($variantSucceedingSibling?->aggregateId, 'succeeding variant');
                \Neos\Flow\var_dump($siblingParent?->aggregateId, 'sibling parent');
                \Neos\Flow\var_dump($variantParentId, 'variant parent');
                if ($variantSucceedingSibling && $siblingParent && (!$variantParentId || $variantParentId->equals($siblingParent->aggregateId))) {
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

            if ($precedingSiblingNodeAggregateId) {
                $variantPrecedingSiblingId = null;
                $variantPrecedingSibling = $variantSubgraph->findNodeById($precedingSiblingNodeAggregateId);
                $variantParentId = $parentNodeAggregateId ?: $variantSubgraph->findParentNode($nodeAggregateId)?->aggregateId;
                $siblingParent = $variantSubgraph->findParentNode($precedingSiblingNodeAggregateId);
                if ($variantPrecedingSibling && $siblingParent && $variantParentId?->equals($siblingParent->aggregateId)) {
                    // c) happy path, the explicitly requested preceding sibling also exists in this dimension space point
                    $variantPrecedingSiblingId = $precedingSiblingNodeAggregateId;
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


    /**
     * Resolves the succeeding siblings for the node to be created in each dimension space points it will cover.
     *
     * For each covered dimension space point
     * a) The requested succeeding sibling is selected it if also covers this dimension space point
     * b) If the requested succeeding sibling does not exist, all the other succeeding siblings of the node in the origin
     * will be checked and the first one covering this dimension space point is used
     * c) As fallback no succeeding sibling is specified
     *
     * Developers hint:
     * Similar to {@see self::resolveInterdimensionalSiblingsForVariation()} except this
     * operates on the explicitly set succeeding sibling instead of the node itself.
     */
    protected function resolveInterdimensionalSiblingsForCreation(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $requestedSucceedingSiblingNodeAggregateId,
        OriginDimensionSpacePoint $sourceOrigin,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
    ): InterdimensionalSiblings {
        $subGraph = $contentGraph->getSubgraph($sourceOrigin->toDimensionSpacePoint(), VisibilityConstraints::createEmpty());
        $originAlternativeSucceedingSiblings = $subGraph->findSucceedingSiblingNodes(
            $requestedSucceedingSiblingNodeAggregateId,
            FindSucceedingSiblingNodesFilter::create()
        );

        $interdimensionalSiblings = [];
        foreach ($coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $subGraph = $contentGraph->getSubgraph($coveredDimensionSpacePoint, VisibilityConstraints::createEmpty());
            $variantSucceedingSibling = $subGraph->findNodeById($requestedSucceedingSiblingNodeAggregateId);
            if ($variantSucceedingSibling) {
                // a) happy path, the explicitly requested succeeding sibling also exists in this dimension space point
                $interdimensionalSiblings[] = new InterdimensionalSibling(
                    $coveredDimensionSpacePoint,
                    $variantSucceedingSibling->aggregateId,
                );
                continue;
            }

            // check the other siblings succeeding in the origin dimension space point
            foreach ($originAlternativeSucceedingSiblings as $originSibling) {
                $alternativeVariantSucceedingSibling = $subGraph->findNodeById($originSibling->aggregateId);
                if (!$alternativeVariantSucceedingSibling) {
                    continue;
                }
                // b) one of the further succeeding sibling exists in this dimension space point
                $interdimensionalSiblings[] = new InterdimensionalSibling(
                    $coveredDimensionSpacePoint,
                    $alternativeVariantSucceedingSibling->aggregateId,
                );
                continue 2;
            }

            // c) fallback; there is no succeeding sibling in this dimension space point
            $interdimensionalSiblings[] = new InterdimensionalSibling(
                $coveredDimensionSpacePoint,
                null,
            );
        }

        return new InterdimensionalSiblings(...$interdimensionalSiblings);
    }

    /**
     * Resolves the succeeding siblings for the node variant to be created and all dimension space points the variant will cover.
     *
     * For each dimension space point in the variant coverage
     * a) All the succeeding siblings of the node aggregate in the source origin are checked
     * and the first one existing in this dimension space point is used
     * b) As fallback no succeeding sibling is specified
     *
     * Developers hint:
     * Similar to {@see self::resolveInterdimensionalSiblingsForCreation()}
     * except this operates on the to-be-varied node itself instead of an explicitly set succeeding sibling
     */
    protected function resolveInterdimensionalSiblingsForVariation(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $varyingNodeAggregateId,
        OriginDimensionSpacePoint $sourceOrigin,
        DimensionSpacePointSet $variantCoverage,
    ): InterdimensionalSiblings {
        $originSiblings = $contentGraph
            ->getSubgraph($sourceOrigin->toDimensionSpacePoint(), VisibilityConstraints::createEmpty())
            ->findSucceedingSiblingNodes($varyingNodeAggregateId, FindSucceedingSiblingNodesFilter::create());

        $interdimensionalSiblings = [];
        foreach ($variantCoverage as $variantDimensionSpacePoint) {
            // check the siblings succeeding in the origin dimension space point
            foreach ($originSiblings as $originSibling) {
                $variantSibling = $contentGraph->getSubgraph($variantDimensionSpacePoint, VisibilityConstraints::createEmpty())->findNodeById($originSibling->aggregateId);
                if (!$variantSibling) {
                    continue;
                }
                // a) one of the further succeeding sibling exists in this dimension space point
                $interdimensionalSiblings[] = new InterdimensionalSibling(
                    $variantDimensionSpacePoint,
                    $variantSibling->aggregateId,
                );
                continue 2;
            }

            // b) fallback; there is no succeeding sibling in this dimension space point
            $interdimensionalSiblings[] = new InterdimensionalSibling(
                $variantDimensionSpacePoint,
                null,
            );
        }

        return new InterdimensionalSiblings(...$interdimensionalSiblings);
    }

    /**
     * @param ?NodeAggregateId $parentNodeAggregateId the parent node aggregate ID to validate variant siblings against.
     *      If no new parent is given, the siblings are validated against the parent of the to-be-moved node in the respective dimension space point.
     * @param bool $completeSet Whether unresolvable siblings should be added as null or not at all
     *                          True when a new parent is set, which will result of the node being added at the end
     *                          True when no preceding sibling is given and the succeeding sibling is explicitly set to null, which will result of the node being added at the end
     *                          False when no new parent is set, which will result in the node not being moved
     */
    protected function resolveInterdimensionalSiblingsForMove(
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
            VisibilityConstraints::createEmpty()
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
                VisibilityConstraints::createEmpty()
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
