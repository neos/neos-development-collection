<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\ValueObject;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeVariantAssignments;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

/**
 * A node move mapping
 *
 * It declares:
 * * The moved node's origin dimension space point
 * * The new succeeding siblings' assignments if given
 * * The dimension space points covered by the hierarchy relations if and only if a new parent was assigned
 * @Flow\Proxy(false)
 */
final class NodeMoveMapping
{
    /**
     * @var OriginDimensionSpacePoint
     */
    private $movedNodeOrigin;

    /**
     * @var NodeVariantAssignments|null
     */
    private $newSucceedingSiblingAssignments;

    /**
     * @var DimensionSpacePointSet
     */
    private $relationDimensionSpacePoints;

    public function __construct(
        OriginDimensionSpacePoint $movedNodeOrigin,
        ?NodeVariantAssignments $newSucceedingSiblingAssignments,
        ?DimensionSpacePointSet $relationDimensionSpacePoints
    ) {
        $this->movedNodeOrigin = $movedNodeOrigin;
        $this->newSucceedingSiblingAssignments = $newSucceedingSiblingAssignments;
        $this->relationDimensionSpacePoints = $relationDimensionSpacePoints;
    }

    public static function fromArray(array $array): NodeMoveMapping
    {
        return new static(
            new OriginDimensionSpacePoint($array['movedNodeOrigin']),
            NodeVariantAssignments::createFromArray($array['newSucceedingSiblingAssignments']),
            isset($array['relationDimensionSpacePoints']) ? new DimensionSpacePointSet($array['relationDimensionSpacePoints']) : null
        );
    }

    public function getMovedNodeOrigin(): OriginDimensionSpacePoint
    {
        return $this->movedNodeOrigin;
    }

    public function getNewSucceedingSiblingAssignments(): NodeVariantAssignments
    {
        return $this->newSucceedingSiblingAssignments;
    }

    public function getRelationDimensionSpacePoints(): ?DimensionSpacePointSet
    {
        return $this->relationDimensionSpacePoints;
    }
}
