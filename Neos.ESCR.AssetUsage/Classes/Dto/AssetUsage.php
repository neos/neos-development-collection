<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Dto;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class AssetUsage
{

    public function __construct(
        public readonly string $assetIdentifier,
        public readonly ContentStreamId $contentStreamIdentifier,
        public readonly string $originDimensionSpacePoint,
        public readonly NodeAggregateId $nodeAggregateIdentifier,
        public readonly string $propertyName,
    ) {
    }
}
