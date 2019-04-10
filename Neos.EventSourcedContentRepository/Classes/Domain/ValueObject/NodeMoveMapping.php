<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\ValueObject;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\Flow\Annotations as Flow;

/**
 * A node move mapping
 *
 * It declares:
 * * The moved node's origin dimension space point
 * * The new parent's origin dimension space point if given
 * * The new succeeding sibling's origin dimension space point if given
 * * The dimension space points covered by the hierarchy relations if and only if a new parent was assigned
 * @Flow\Proxy(false)
 */
final class NodeMoveMapping
{
    /**
     * @var DimensionSpacePoint
     */
    private $movedNodeOrigin;

    /**
     * @var DimensionSpacePoint|null
     */
    private $newParentNodeOrigin;

    /**
     * @var DimensionSpacePoint|null
     */
    private $newSucceedingSiblingOrigin;

    /**
     * @var DimensionSpacePointSet
     */
    private $relationDimensionSpacePoints;

    public function __construct(
        DimensionSpacePoint $movedNodeOrigin,
        ?DimensionSpacePoint $newParentNodeOrigin,
        ?DimensionSpacePoint $newSucceedingSiblingOrigin,
        ?DimensionSpacePointSet $relationDimensionSpacePoints
    ) {
        if (is_null($newParentNodeOrigin)) {
            if (is_null($newSucceedingSiblingOrigin)) {
                throw new NodeMoveMappingIsInvalid('Node move mapping has neither new parent nor new succeeding sibling origin given.', 1554905753);
            }
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
        $this->newSucceedingSiblingOrigin = $newSucceedingSiblingOrigin;
        $this->relationDimensionSpacePoints = $relationDimensionSpacePoints;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            new DimensionSpacePoint($array['movedNodeOrigin']),
            isset($array['newParentNodeOrigin']) ? new DimensionSpacePoint($array['newParentNodeOrigin']) : null,
            isset($array['newSucceedingSiblingOrigin']) ? new DimensionSpacePoint($array['newSucceedingSiblingOrigin']) : null,
            isset($array['coveredDimensionSpacePointSet']) ? new DimensionSpacePointSet($array['coveredDimensionSpacePointSet']) : null
        );
    }

    public function getMovedNodeOrigin(): DimensionSpacePoint
    {
        return $this->movedNodeOrigin;
    }

    public function getNewParentNodeOrigin(): ?DimensionSpacePoint
    {
        return $this->newParentNodeOrigin;
    }

    public function getNewSucceedingSiblingOrigin(): ?DimensionSpacePoint
    {
        return $this->newSucceedingSiblingOrigin;
    }

    public function getRelationDimensionSpacePoints(): ?DimensionSpacePointSet
    {
        return $this->relationDimensionSpacePoints;
    }
}
