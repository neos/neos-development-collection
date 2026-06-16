<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayer;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelationId;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;

/**
 * @internal
 */
final readonly class ChildHierarchyRelation
{
    public function __construct(
        public HierarchyRelationId $id,
        public ContentStreamLayer $contentStreamLayer,
        public NodeRelationAnchorPoint $childNodeAnchor,
    ) {
    }

    /** @param array<int|string,mixed> $array */
    public static function fromArray(array $array): self
    {
        return new self(
            id: HierarchyRelationId::fromInt($array['id']),
            contentStreamLayer: ContentStreamLayer::fromInt($array['contentstreamlayer']),
            childNodeAnchor: NodeRelationAnchorPoint::fromInteger($array['childnodeanchor']),
        );
    }
}
