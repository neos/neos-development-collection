<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Domain;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * @api
 */
#[Flow\Proxy(false)]
final readonly class AssetUsage
{
    public function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public string $assetId,
        public WorkspaceName $workspaceName,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        public NodeAggregateId $nodeAggregateId,
        public string $propertyName,
    ) {
    }
}
