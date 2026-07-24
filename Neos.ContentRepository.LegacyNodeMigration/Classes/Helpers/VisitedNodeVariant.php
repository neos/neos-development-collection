<?php

declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final readonly class VisitedNodeVariant
{

    public function __construct(
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        public NodeAggregateId $parentNodeAggregateId,
        // the property names this variant holds; a variant created from it copies exactly these
        public PropertyNames $propertyNames,
    ) {}
}
