<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Flow\Annotations as Flow;

/**
 * A node variant that was hidden in the legacy data and is disabled via a deferred SubtreeWasTagged
 * event at the end of the migration, once all variants of its node aggregate are known.
 *
 * @Flow\Proxy(false)
 */
final readonly class HiddenNodeVariant
{
    public function __construct(
        public NodeAggregateId $nodeAggregateId,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
    ) {
    }
}
