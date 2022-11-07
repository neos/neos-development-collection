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
        public readonly SucceedingSiblingNodeMoveDestination|ParentNodeMoveDestination $destination,
    ) {
    }

    public static function createForNewSucceedingSibling(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        SucceedingSiblingNodeMoveDestination $newSucceedingSibling
    ): self {
        return new self($coveredDimensionSpacePoint, $newSucceedingSibling);
    }

    public static function createForNewParent(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        ParentNodeMoveDestination $newParent
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
                SucceedingSiblingNodeMoveDestination::fromArray($array['newSucceedingSibling']),
            );
        } elseif (!empty($array['newParent'])) {
            return new self(
                DimensionSpacePoint::fromArray($array['coveredDimensionSpacePoint']),
                ParentNodeMoveDestination::fromArray($array['newParent']),
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
        if ($this->destination instanceof SucceedingSiblingNodeMoveDestination) {
            return [
                'coveredDimensionSpacePoint' => $this->coveredDimensionSpacePoint,
                'newSucceedingSibling' => $this->destination
            ];
        } elseif ($this->destination instanceof ParentNodeMoveDestination) {
            return [
                'coveredDimensionSpacePoint' => $this->coveredDimensionSpacePoint,
                'newParent' => $this->destination
            ];
        } else {
            throw new \RuntimeException('!!!');
        }
    }
}
