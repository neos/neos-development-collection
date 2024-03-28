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
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @internal implementation details of command handlers
 */
trait NodeCreationInternals
{
    private function resolveInterdimensionalSiblingsForCreation(
        ContentRepository $contentRepository,
        ContentStreamId $contentStreamId,
        NodeAggregateId $requestedSucceedingSiblingNodeAggregateId,
        OriginDimensionSpacePoint $sourceOrigin,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
    ): InterdimensionalSiblings {
        $interdimensionalSiblings = [];
        $originSubgraph = $contentRepository->getContentGraph()->getSubgraph(
            $contentStreamId,
            $sourceOrigin->toDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );
        $originAlternativeSucceedingSiblings = $originSubgraph->findSucceedingSiblingNodes(
            $requestedSucceedingSiblingNodeAggregateId,
            FindSucceedingSiblingNodesFilter::create()
        );

        foreach ($coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $variantSubgraph = $contentRepository->getContentGraph()->getSubgraph(
                $contentStreamId,
                $coveredDimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );
            $variantSucceedingSibling = $variantSubgraph->findNodeById($requestedSucceedingSiblingNodeAggregateId);
            if (!$variantSucceedingSibling) {
                foreach ($originAlternativeSucceedingSiblings as $originSibling) {
                    $variantSucceedingSibling = $variantSubgraph->findNodeById($originSibling->nodeAggregateId);
                    if ($variantSucceedingSibling instanceof Node) {
                        break;
                    }
                }
            }

            $interdimensionalSiblings[] = new InterdimensionalSibling(
                $coveredDimensionSpacePoint,
                $variantSucceedingSibling?->nodeAggregateId,
            );
        }

        return new InterdimensionalSiblings(...$interdimensionalSiblings);
    }
}
