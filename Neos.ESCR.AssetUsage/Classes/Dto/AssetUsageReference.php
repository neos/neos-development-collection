<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Dto;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Dto\UsageReference;

/**
 * @Flow\Proxy(false)
 */
final class AssetUsageReference extends UsageReference
{

    public function __construct
    (
        Asset $asset,
        public readonly string $assetIdentifier,
        public readonly ContentStreamId $contentStreamIdentifier,
        public readonly string $originDimensionSpacePoint,
        public readonly NodeAggregateId $nodeAggregateIdentifier,
        public readonly string $propertyName,
    ) {
        parent::__construct($asset);
    }
}
