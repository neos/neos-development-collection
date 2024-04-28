<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * @api
 */
#[Flow\Proxy(false)]
final readonly class AssetUsage
{
    public function __construct(
        public string $assetId,
        public ContentStreamId $contentStreamId,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        public NodeAggregateId $nodeAggregateId,
        public string $propertyName,
    ) {
    }
}
