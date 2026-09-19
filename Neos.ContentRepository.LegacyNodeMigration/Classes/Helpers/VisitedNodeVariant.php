<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final readonly class VisitedNodeVariant
{

    /**
     * @param DimensionSpacePointSet $claimedDimensionSpacePoints The dimension space points the exported creation/variation event of this variant covers
     */
    public function __construct(
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        public NodeAggregateId $parentNodeAggregateId,
        // the property names this variant holds; a variant created from it copies exactly these
        public PropertyNames $propertyNames,
        public DimensionSpacePointSet $claimedDimensionSpacePoints
    ) {}
}
