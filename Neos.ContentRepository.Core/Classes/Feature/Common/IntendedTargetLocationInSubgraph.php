<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * An intended target location of a node in a subgraph
 *
 * @internal to be used by command handlers
 */
final readonly class IntendedTargetLocationInSubgraph
{
    private function __construct(
        /**
         * The ID of the affected node aggregate
         */
        public NodeAggregateId $nodeAggregateId,
        /**
         * The dimension space point of the targeted subgraph
         */
        public DimensionSpacePoint $dimensionSpacePoint,
        /**
         * The ID of the targeted parent node aggregate
         * If set to null, means that the operation does not change the affected node's parent
         */
        public ?NodeAggregateId $parentNodeAggregateId,
        /**
         * The ID of the targeted succeeding node aggregate.
         * Takes precedence over the $precedingSiblingNodeAggregateId, i.e.
         *  - if set and any (further) succeeding sibling can be resolved, the $precedingSiblingNodeAggregateId parameter is ignored
         *  - if both parameters are null, the _end_ of the sibling list is targeted
         * Effectively targets the position _before_ this ID
         */
        public ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        /**
         * The ID of the targeted preceding node aggregate.
         * Can be set to improve precision in succeeding sibling resolution, so that if no direct succeeding sibling can be resolved,
         * succeeding siblings of this preceding sibling can be evaluated as further candidates.
         * Effectively targets the position _after_ this ID
         */
        public ?NodeAggregateId $precedingSiblingNodeAggregateId,
    ) {
    }

    public static function create(
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint,
        ?NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
    ): self {
        return new self(
            nodeAggregateId: $nodeAggregateId,
            dimensionSpacePoint: $dimensionSpacePoint,
            parentNodeAggregateId: $parentNodeAggregateId,
            succeedingSiblingNodeAggregateId: $succeedingSiblingNodeAggregateId,
            precedingSiblingNodeAggregateId: $precedingSiblingNodeAggregateId,
        );
    }
}
