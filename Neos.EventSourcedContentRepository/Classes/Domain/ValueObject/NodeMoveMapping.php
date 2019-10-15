<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\ValueObject;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeVariantAssignments;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

/**
 * A node move mapping
 *
 * It declares:
 * * The moved node's origin dimension space point
 * * The new parent's origin dimension space point if given
 * * The new succeeding sibling's assigment if given
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
     * @var DimensionSpacePoint|null
     */
    private $newParentNodeOrigin;

    /**
     * @var NodeVariantAssignments|null
     */
    private $newSucceedingSiblingAssignments;

    /**
     * @var DimensionSpacePointSet
     */
    private $relationDimensionSpacePoints;

    public function __construct(
        ?DimensionSpacePoint $newParentNodeOrigin,
        OriginDimensionSpacePoint $movedNodeOrigin,
        ?NodeVariantAssignments $newSucceedingSiblingAssignments,
        ?DimensionSpacePointSet $relationDimensionSpacePoints
    ) {
        if (is_null($newParentNodeOrigin)) {
            if (!is_null($relationDimensionSpacePoints)) {
                throw new NodeMoveMappingIsInvalid('Node move mapping has no new parent origin but relation dimension space points given.', 1554905915);
            }
        } else {
            if (is_null($relationDimensionSpacePoints)) {
                throw new NodeMoveMappingIsInvalid('Node move mapping has a new parent origin but no relation dimension space points given.', 1554905920);
            }
        }
        $this->movedNodeOrigin = $movedNodeOrigin;
        $this->newParentNodeOrigin = $newParentNodeOrigin;
        $this->newSucceedingSiblingAssignments = $newSucceedingSiblingAssignments;
        $this->relationDimensionSpacePoints = $relationDimensionSpacePoints;
    }

    public static function fromArray(array $array): NodeMoveMapping
    {
        return new static(
            isset($array['newParentNodeOrigin']) ? new DimensionSpacePoint($array['newParentNodeOrigin']) : null,
            new OriginDimensionSpacePoint($array['movedNodeOrigin']),
            NodeVariantAssignments::createFromArray($array['newSucceedingSiblingAssignments']),
            isset($array['relationDimensionSpacePoints']) ? new DimensionSpacePointSet($array['relationDimensionSpacePoints']) : null
        );
    }

    public function getMovedNodeOrigin(): OriginDimensionSpacePoint
    {
        return $this->movedNodeOrigin;
    }

    public function getNewParentNodeOrigin(): ?DimensionSpacePoint
    {
        return $this->newParentNodeOrigin;
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
