<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayer;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelationId;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;

/**
 * @internal
 */
final readonly class HierarchyRelationRow
{
    public function __construct(
        public HierarchyRelationId $id,
        public ContentStreamLayer $contentStreamLayer,
        public NodeRelationAnchorPoint $parentNodeAnchor,
        public NodeRelationAnchorPoint $childNodeAnchor,
        public int $position,
        public string $dimensionSpacePointHash,
        public ?string $subtreeTags,
    ) {
    }

    /** @param array<int|string,mixed> $array */
    public static function fromArray(array $array): self
    {
        return new self(
            id: HierarchyRelationId::fromInt($array['id']),
            contentStreamLayer: ContentStreamLayer::fromInt($array['contentstreamlayer']),
            parentNodeAnchor: NodeRelationAnchorPoint::fromInteger($array['parentnodeanchor']),
            childNodeAnchor: NodeRelationAnchorPoint::fromInteger($array['childnodeanchor']),
            position: $array['position'],
            dimensionSpacePointHash: $array['dimensionspacepointhash'],
            subtreeTags: $array['subtreetags'] ?? null,
        );
    }
}
