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

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * @internal implementation details of command handlers
 */
trait NodeCreationInternals
{
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
     * Similar to {@see NodeVariationInternals::resolveInterdimensionalSiblings()} except this
     * operates on the explicitly set succeeding sibling instead of the node itself.
     */
    private function resolveInterdimensionalSiblingsForCreation(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $requestedSucceedingSiblingNodeAggregateId,
        OriginDimensionSpacePoint $sourceOrigin,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
    ): InterdimensionalSiblings {
        $subGraph = $contentGraph->getSubgraph($sourceOrigin->toDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());
        $originAlternativeSucceedingSiblings = $subGraph->findSucceedingSiblingNodes(
            $requestedSucceedingSiblingNodeAggregateId,
            FindSucceedingSiblingNodesFilter::create()
        );

        $interdimensionalSiblings = [];
        foreach ($coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $subGraph = $contentGraph->getSubgraph($coveredDimensionSpacePoint, VisibilityConstraints::withoutRestrictions());
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
}
