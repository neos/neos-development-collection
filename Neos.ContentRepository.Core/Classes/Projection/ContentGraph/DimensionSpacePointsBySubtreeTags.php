<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use JsonSerializable;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;

/**
 * @api used by {@see NodeAggregate}
 */
final readonly class DimensionSpacePointsBySubtreeTags implements JsonSerializable
{
    /**
     * @param array<string,DimensionSpacePointSet> $dimensionSpacePointsBySubtreeTags
     */
    public function __construct(
        private array $dimensionSpacePointsBySubtreeTags,
    ) {
    }

    public static function create(): self
    {
        return new self([]);
    }

    public function withSubtreeTagAndDimensionSpacePoint(SubtreeTag $subtreeTag, DimensionSpacePoint $dimensionSpacePoint): self
    {
        $dimensionSpacePointsBySubtreeTags = $this->dimensionSpacePointsBySubtreeTags;
        if (!array_key_exists($subtreeTag->value, $dimensionSpacePointsBySubtreeTags)) {
            $dimensionSpacePointsBySubtreeTags[$subtreeTag->value] = DimensionSpacePointSet::fromArray([]);
        }
        if ($dimensionSpacePointsBySubtreeTags[$subtreeTag->value]->contains($dimensionSpacePoint)) {
            return $this;
        }
        $dimensionSpacePointsBySubtreeTags[$subtreeTag->value] = $dimensionSpacePointsBySubtreeTags[$subtreeTag->value]->getUnion(DimensionSpacePointSet::fromArray([$dimensionSpacePoint]));
        return new self($dimensionSpacePointsBySubtreeTags);
    }

    public function forSubtreeTag(SubtreeTag $subtreeTag): DimensionSpacePointSet
    {
        return $this->dimensionSpacePointsBySubtreeTags[$subtreeTag->value] ?? DimensionSpacePointSet::fromArray([]);
    }

    /**
     * @return array<string,DimensionSpacePointSet>
     */
    public function jsonSerialize(): array
    {
        return $this->dimensionSpacePointsBySubtreeTags;
    }
}
