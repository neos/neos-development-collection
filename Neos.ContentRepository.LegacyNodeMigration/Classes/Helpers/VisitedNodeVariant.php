<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class VisitedNodeVariant
{

    public function __construct(
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        public readonly NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ) {}
}
