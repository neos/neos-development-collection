<?php

namespace Neos\ContentRepository\Projection\ContentGraph;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;

/**
 *
 * This is part of the node's "Read Model" identity, which is defined by:
 * - {@see getContentStreamIdentifier}
 * - {@see getNodeAggregateIdentifier}
 * - {@see getDimensionSpacePoint}
 * - {@see getVisibilityConstraints}
 *
 * With the above information, you can fetch a Node Accessor using {@see NodeAccessorManager::accessorFor()}, or
 * (for lower-level access) a Subgraph using {@see ContentGraphInterface::getSubgraphByIdentifier()}.
 *
 * ## DimensionSpacePoint
 *
 *This is the DimensionSpacePoint this node has been accessed in
 * - NOT the DimensionSpacePoint where the node is "at home".
 * The DimensionSpacePoint where the node is (at home) is called the ORIGIN DimensionSpacePoint,
 * and this can be accessed using {@see NodeInterface::getOriginDimensionSpacePoint}. If in doubt, you'll
 * usually need this method instead of the Origin DimensionSpacePoint.
 *
 *
 * // TODO: DESCRIBE PROPERLY
 */
final class ContentSubgraphIdentity
{
    public function __construct(
        public readonly ContentRepositoryIdentifier $contentRepositoryIdentifier,
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        /**
         * DimensionSpacePoint a node has been accessed in.
         */
        public readonly DimensionSpacePoint $dimensionSpacePoint,
        public readonly VisibilityConstraints $visibilityConstraints,
    ) {
    }

    public function equals(ContentSubgraphIdentity $getSubgraphIdentity): bool
    {
        // TODO IMPL
    }
}
