<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeMove\Dto;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

/**
 * A move mapping for a single *materialized* node in a given OriginDimensionSpacePoint, including
 * all of its incoming edges.
 *
 * See the docs of {@see NodeAggregateWasMoved} for a full description.
 *
 * @api DTO of {@see NodeAggregateWasMoved} event
 */
final class OriginNodeMoveMapping implements \JsonSerializable
{
    public function __construct(
        public readonly OriginDimensionSpacePoint $movedNodeOrigin,
        public readonly CoverageNodeMoveMappings $newLocations
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            OriginDimensionSpacePoint::fromArray($array['movedNodeOrigin']),
            CoverageNodeMoveMappings::fromArray($array['newLocations']),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'movedNodeOrigin' => $this->movedNodeOrigin,
            'newLocations' => $this->newLocations,
        ];
    }
}
