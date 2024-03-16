<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\Dto\UsageReference;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

/**
 * @api
 */
#[Flow\Proxy(false)]
final class AssetUsageReference extends UsageReference
{
    public function __construct(
        AssetInterface $asset,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly ContentStreamId $contentStreamId,
        private readonly OriginDimensionSpacePoint $originDimensionSpacePointHash,
        private readonly NodeAggregateId $nodeAggregateId,
    ) {
        parent::__construct($asset);
    }

    public function getContentRepositoryId(): ContentRepositoryId
    {
        return $this->contentRepositoryId;
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->originDimensionSpacePointHash;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }
}
