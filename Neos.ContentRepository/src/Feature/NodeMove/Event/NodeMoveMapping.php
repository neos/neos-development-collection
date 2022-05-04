<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\NodeMove\Event;

use Neos\ContentRepository\Feature\NodeMove\Event\NodeVariantAssignments;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

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
 */
#[Flow\Proxy(false)]
final class NodeMoveMapping
{
    private OriginDimensionSpacePoint $movedNodeOrigin;

    private NodeVariantAssignments $newParentAssignments;

    private NodeVariantAssignments $newSucceedingSiblingAssignments;

    public function __construct(
        OriginDimensionSpacePoint $movedNodeOrigin,
        NodeVariantAssignments $newParentAssignments,
        NodeVariantAssignments $newSucceedingSiblingAssignments
    ) {
        $this->movedNodeOrigin = $movedNodeOrigin;
        $this->newParentAssignments = $newParentAssignments;
        $this->newSucceedingSiblingAssignments = $newSucceedingSiblingAssignments;
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

    public function getMovedNodeOrigin(): OriginDimensionSpacePoint
    {
        return $this->movedNodeOrigin;
    }

    public function getNewParentAssignments(): NodeVariantAssignments
    {
        return $this->newParentAssignments;
    }

    public function getNewSucceedingSiblingAssignments(): NodeVariantAssignments
    {
        return $this->newSucceedingSiblingAssignments;
    }
}
