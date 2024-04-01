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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @internal implementation details of command handlers
 */
trait NodeCreationInternals
{
    /**
     * Resolves the succeeding sibling anchor where a node should be created.
     *
     * a) The requested anchor point will be taken into account if existing in the covered dimension
     * b) If the requested sibling does not exist, all the other succeeding siblings of the requested
     * will be checked and the first one used if existing in the covered dimension.
     * c) As fallback no succeeding sibling will be specified
     */
    private function resolveInterdimensionalSiblingsForCreation(
        ContentRepository $contentRepository,
        ContentStreamId $contentStreamId,
        NodeAggregateId $requestedSucceedingSiblingNodeAggregateId,
        OriginDimensionSpacePoint $sourceOrigin,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
    ): InterdimensionalSiblings {
        $originSubgraph = $contentRepository->getContentGraph()->getSubgraph(
            $contentStreamId,
            $sourceOrigin->toDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );
        $originAlternativeSucceedingSiblings = $originSubgraph->findSucceedingSiblingNodes(
            $requestedSucceedingSiblingNodeAggregateId,
            FindSucceedingSiblingNodesFilter::create()
        );

        $interdimensionalSiblings = [];
        foreach ($coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $variantSubgraph = $contentRepository->getContentGraph()->getSubgraph(
                $contentStreamId,
                $coveredDimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );
            $variantSucceedingSibling = $variantSubgraph->findNodeById($requestedSucceedingSiblingNodeAggregateId);
            if ($variantSucceedingSibling) {
                // a) happy case, the node also exist in this dimension
                $interdimensionalSiblings[] = new InterdimensionalSibling(
                    $coveredDimensionSpacePoint,
                    $variantSucceedingSibling->nodeAggregateId,
                );
                continue;
            }

            foreach ($originAlternativeSucceedingSiblings as $originSibling) {
                $alternativeVariantSucceedingSibling = $variantSubgraph->findNodeById($originSibling->nodeAggregateId);
                if (!$alternativeVariantSucceedingSibling) {
                    continue;
                }
                // b) one of the other alternative succeeding siblings also exist in this dimension
                $interdimensionalSiblings[] = new InterdimensionalSibling(
                    $coveredDimensionSpacePoint,
                    $alternativeVariantSucceedingSibling->nodeAggregateId,
                );
                continue 2;
            }

            // c) fallback, there is no succeeding sibling in this dimension
            $interdimensionalSiblings[] = new InterdimensionalSibling(
                $coveredDimensionSpacePoint,
                null,
            );
        }

        return new InterdimensionalSiblings(...$interdimensionalSiblings);
    }
}
