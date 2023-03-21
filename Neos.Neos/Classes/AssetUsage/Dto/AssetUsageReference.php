<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\Dto\UsageReference;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepositoryNodeInformationInterface;

/**
 * @Flow\Proxy(false)
 * @api
 */
final class AssetUsageReference extends UsageReference implements ContentRepositoryNodeInformationInterface
{
    public function __construct(
        AssetInterface $asset,
        private readonly ContentStreamId $contentStreamId,
        private readonly string $originDimensionSpacePointHash,
        private readonly NodeAggregateId $nodeAggregateId,
    ) {
        parent::__construct($asset);
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getOriginDimensionSpacePointHash(): string
    {
        return $this->originDimensionSpacePointHash;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }
}
