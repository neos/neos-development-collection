<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\NodeMove\Event;

use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;

/**
 * A move mapping for a single node
 *
 * It declares:
 * * The moved node's origin dimension space point.
 *      With this the node can be uniquely identified (as we are moving a single NodeAggregate)
 * * The new parent assignments if given - the node might be assigned to different parents,
 *      depending on covered dimension space point
 * * The new succeeding siblings' assignments if given - the node might be assigned to different succeeding siblings,
 *      depending on covered dimension space point
 *
 * @api DTO of {@see NodeAggregateWasMoved} event
 */
final class NodeMoveMapping implements \JsonSerializable
{
    public function __construct(
        public readonly OriginDimensionSpacePoint $movedNodeOrigin,
        public readonly NodeVariantAssignments $newParentAssignments,
        public readonly NodeVariantAssignments $newSucceedingSiblingAssignments
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            OriginDimensionSpacePoint::fromArray($array['movedNodeOrigin']),
            NodeVariantAssignments::createFromArray($array['newParentAssignments']),
            NodeVariantAssignments::createFromArray($array['newSucceedingSiblingAssignments'])
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'movedNodeOrigin' => $this->movedNodeOrigin,
            'newParentAssignments' => $this->newParentAssignments,
            'newSucceedingSiblingAssignments' => $this->newSucceedingSiblingAssignments
        ];
    }
}
