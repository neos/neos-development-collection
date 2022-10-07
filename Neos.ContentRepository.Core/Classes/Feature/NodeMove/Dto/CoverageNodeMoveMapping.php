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
        if ($this->newSucceedingSibling === null && $this->newParent === null) {
            throw new \InvalidArgumentException(
                'CoverageNodeMoveMapping needs either newSucceedingSibling or newParent set; '
                . 'but you did not set any of them.', 1664964290
            );
        }

        if ($this->newSucceedingSibling !== null && $this->newParent !== null) {
            throw new \InvalidArgumentException(
                'CoverageNodeMoveMapping needs either newSucceedingSibling or newParent set; '
                . 'but you set both of them.', 1664964333
            );
        }
    }

    public static function createForNewSucceedingSibling(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeMoveTarget $newSucceedingSibling
    ): self {
        return new self($coveredDimensionSpacePoint, $newSucceedingSibling, null);
    }

    public static function createForNewParent(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeMoveTarget $newParent
    ): self {
        return new self($coveredDimensionSpacePoint, null, $newParent);
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            DimensionSpacePoint::fromArray($array['coveredDimensionSpacePoint']),
            NodeMoveTarget::fromArray($array['newSucceedingSibling']),
            NodeMoveTarget::fromArray($array['newParent'])
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'coveredDimensionSpacePoint' => $this->coveredDimensionSpacePoint,
            'newSucceedingSibling' => $this->newSucceedingSibling,
            'newParent' => $this->newParent
        ];
    }
}
