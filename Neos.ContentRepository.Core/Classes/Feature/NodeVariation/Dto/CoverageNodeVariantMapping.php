<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeVariation\Dto;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;

/**
 * See the docs of {@see NodePeerVariantWasCreated} and {@see NodeGeneralizationVariantWasCreated} for a full description.
 *
 * @api DTO of {@see NodeAggregateWasMoved} event
 */
final class CoverageNodeVariantMapping implements \JsonSerializable
{
    private function __construct(
        public readonly DimensionSpacePoint $coveredDimensionSpacePoint,
        public readonly SucceedingSiblingVariantPosition|EndSiblingVariantPosition $destination,
    ) {
    }

    public static function createForNewSucceedingSibling(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        SucceedingSiblingVariantPosition $newSucceedingSibling
    ): self {
        return new self($coveredDimensionSpacePoint, $newSucceedingSibling);
    }

    public static function createForNewEndSibling(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        EndSiblingVariantPosition $newSucceedingSibling
    ): self {
        return new self($coveredDimensionSpacePoint, $newSucceedingSibling);
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        if (!empty($array['newSucceedingSibling'])) {
            return new self(
                DimensionSpacePoint::fromArray($array['coveredDimensionSpacePoint']),
                SucceedingSiblingVariantPosition::fromArray($array['newSucceedingSibling']),
            );
        } elseif (!empty($array['endSibling'])) {
            return new self(
                DimensionSpacePoint::fromArray($array['coveredDimensionSpacePoint']),
                EndSiblingVariantPosition::create(),
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
        if ($this->destination instanceof SucceedingSiblingVariantPosition) {
            return [
                'coveredDimensionSpacePoint' => $this->coveredDimensionSpacePoint,
                'newSucceedingSibling' => $this->destination
            ];
        } elseif ($this->destination instanceof EndSiblingVariantPosition) {
            return [
                'endSibling' => true,
            ];
        } else {
            throw new \RuntimeException('!!!');
        }
    }
}
