<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeMove\Dto;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;

/**
 * See the docs of {@see NodeAggregateWasMoved} for a full description.
 *
 * @api DTO of {@see NodeAggregateWasMoved} event
 */
final class CoverageNodeMoveMapping implements \JsonSerializable
{
    private function __construct(
        public readonly DimensionSpacePoint $coveredDimensionSpacePoint,
        public readonly SucceedingSiblingNodeMoveTarget|ParentNodeMoveTarget $target,
    ) {
    }

    public static function createForNewSucceedingSibling(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        SucceedingSiblingNodeMoveTarget $newSucceedingSibling
    ): self {
        return new self($coveredDimensionSpacePoint, $newSucceedingSibling);
    }

    public static function createForNewParent(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        ParentNodeMoveTarget $newParent
    ): self {
        return new self($coveredDimensionSpacePoint, $newParent);
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        if (!empty($array['newSucceedingSibling'])) {
            return new self(
                DimensionSpacePoint::fromArray($array['coveredDimensionSpacePoint']),
                SucceedingSiblingNodeMoveTarget::fromArray($array['newSucceedingSibling']),
            );
        } elseif (!empty($array['newParent'])) {
            return new self(
                DimensionSpacePoint::fromArray($array['coveredDimensionSpacePoint']),
                ParentNodeMoveTarget::fromArray($array['newParent']),
            );
        } else {
            throw new \RuntimeException('!!!');
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        if ($this->target instanceof SucceedingSiblingNodeMoveTarget) {
            return [
                'coveredDimensionSpacePoint' => $this->coveredDimensionSpacePoint,
                'newSucceedingSibling' => $this->target
            ];
        } elseif ($this->target instanceof ParentNodeMoveTarget) {
            return [
                'coveredDimensionSpacePoint' => $this->coveredDimensionSpacePoint,
                'newParent' => $this->target
            ];
        } else {
            throw new \RuntimeException('!!!');
        }
    }
}
