<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The active record for reading and writing nodes from and to memory
 *
 * @internal
 */
final class InMemoryNodeRecord
{
    /**
     * @param array<string,InMemoryHierarchyHyperrelationRecordSet> $parentsByContentStreamId
     * @param array<string,InMemoryHierarchyHyperrelationRecordSet> $childrenByContentStream
     */
    public function __construct(
        public NodeAggregateId $nodeAggregateId,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        public SerializedPropertyValues $properties,
        public NodeTypeName $nodeTypeName,
        public NodeAggregateClassification $classification,
        public ?NodeName $name,
        public Timestamps $timestamps,
        public array $parentsByContentStreamId,
        public array $childrenByContentStream,
        public SubtreeTags $tags,
        public SubtreeTags $inheritedTags,
    ) {
    }

    public function coversDimensionSpacePoint(ContentStreamId $contentStreamId, DimensionSpacePoint $dimensionSpacePoint): bool
    {
        return $this->getCoveredDimensionSpacePointSet($contentStreamId)
            ->contains($dimensionSpacePoint);
    }

    public function getCoveredDimensionSpacePointSet(ContentStreamId $contentStreamId): DimensionSpacePointSet
    {
        return $this->parentsByContentStreamId[$contentStreamId->value]
            ->getCoveredDimensionSpacePointSet();
    }
}
