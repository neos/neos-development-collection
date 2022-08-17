<?php

namespace Neos\ContentRepository\Projection\ContentGraph;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

/**
 * This describes a node's read model identity parts which are rooted in the {@see ContentSubgraphInterface}, namely:
 * - {@see contentRepositoryIdentifier}
 * - {@see contentStreamIdentifier}
 * - {@see dimensionSpacePoint}
 * - {@see visibilityConstraints}
 *
 * In addition to the above subgraph identity, a Node's read model identity is further described
 * by {@see Node::$nodeAggregateIdentifier}.
 *
 * With the above information, you can fetch a Subgraph directly by using
 * {@see ContentRepositoryRegistry::subgraphForNode()}.
 *
 * (a bit lower-level): For Non-Neos/Flow installations, you can fetch a Subgraph using
 * {@see ContentGraphInterface::getSubgraph()}.
 *
 * ## A note about the referenced DimensionSpacePoint
 *
 * This is the DimensionSpacePoint this node has been accessed in, and NOT the DimensionSpacePoint where the
 * node is "at home".
 * The DimensionSpacePoint where the node is (at home) is called the ORIGIN DimensionSpacePoint,
 * and this can be accessed using {@see Node::originDimensionSpacePoint}. If in doubt, you'll
 * usually need this method instead of the Origin DimensionSpacePoint inside the read model; and you'll
 * need the OriginDimensionSpacePoint when constructing commands on the write side.
 *
 * @api
 */
final class ContentSubgraphIdentity
{
    private function __construct(
        public readonly ContentRepositoryIdentifier $contentRepositoryIdentifier,
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        /**
         * DimensionSpacePoint a node has been accessed in.
         */
        public readonly DimensionSpacePoint $dimensionSpacePoint,
        public readonly VisibilityConstraints $visibilityConstraints,
    ) {
    }

    /**
     * @api
     */
    public static function create(
        ContentRepositoryIdentifier $contentRepositoryIdentifier,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): self {
        return new self(
            $contentRepositoryIdentifier,
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            $visibilityConstraints
        );
    }

    public function equals(ContentSubgraphIdentity $other): bool
    {
        return $this->contentRepositoryIdentifier->equals($other->contentRepositoryIdentifier)
            && $this->contentStreamIdentifier->equals($other->contentStreamIdentifier)
            && $this->dimensionSpacePoint->equals($other->dimensionSpacePoint)
            && $this->visibilityConstraints->getHash() === $other->visibilityConstraints->getHash();
    }

    public function withContentStreamIdentifier(ContentStreamIdentifier $contentStreamIdentifier): self
    {
        return new self(
            $this->contentRepositoryIdentifier,
            $contentStreamIdentifier,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints
        );
    }

    public function withVisibilityConstraints(VisibilityConstraints $visibilityConstraints): self
    {
        return new self(
            $this->contentRepositoryIdentifier,
            $this->contentStreamIdentifier,
            $this->dimensionSpacePoint,
            $visibilityConstraints
        );
    }
}
