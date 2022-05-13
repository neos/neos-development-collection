<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Dto;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class AssetUsage
{

    public function __construct(
        public readonly string $assetIdentifier,
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly string $originDimensionSpacePoint,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly string $propertyName,
    ) {
    }
}
