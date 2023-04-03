<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

/**
 * @Flow\Proxy(false)
 * @api
 */
final class AssetUsage
{
    public function __construct(
        public readonly string $assetId,
        public readonly ContentStreamId $contentStreamId,
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly string $propertyName,
    ) {
    }
}
