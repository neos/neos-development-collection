<?php

namespace Neos\ContentRepository\Projection\ContentGraph;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;

/**
 * This describes a node's read model identity parts which are rooted in the {@see ContentSubgraphInterface}, namely:
 * - {@see contentRepositoryIdentifier}
 * - {@see contentStreamIdentifier}
 * - {@see dimensionSpacePoint}
 * - {@see visibilityConstraints}
 *
 * In addition to the above subgraph identity, a Node's read model identity is further described
 * by {@see NodeInterface::getNodeAggregateIdentifier()}.
 *
 * With the above information, you can fetch a Subgraph using {@see ContentGraphInterface::getSubgraphByIdentifier()}.
 *
 * ## A note about the referenced DimensionSpacePoint
 *
 * This is the DimensionSpacePoint this node has been accessed in, and NOT the DimensionSpacePoint where the
 * node is "at home".
 * The DimensionSpacePoint where the node is (at home) is called the ORIGIN DimensionSpacePoint,
 * and this can be accessed using {@see NodeInterface::getOriginDimensionSpacePoint}. If in doubt, you'll
 * usually need this method instead of the Origin DimensionSpacePoint inside the read model; and you'll
 * need the OriginDimensionSpacePoint when constructing commands on the write side.
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

    public function equals(ContentSubgraphIdentity $other): bool
    {
        return $this->contentRepositoryIdentifier->equals($other->contentRepositoryIdentifier)
            && $this->contentStreamIdentifier->equals($other->contentStreamIdentifier)
            && $this->dimensionSpacePoint->equals($other->dimensionSpacePoint)
            && $this->visibilityConstraints->getHash() === $other->visibilityConstraints->getHash();
    }
}
