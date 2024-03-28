<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * Addresses a node within the AssetUsage package
 *
 * We don't use the official NodeAddress here {@see \Neos\Neos\FrontendRouting\NodeAddress},
 * As we don't need the workspaceName information, and just a simple pointer to a node.
 *
 * @internal
 */
#[Flow\Proxy(false)]
final readonly class AssetUsageNodeAddress
{
    public function __construct(
        public ContentStreamId $contentStreamId,
        public DimensionSpacePoint $dimensionSpacePoint,
        public NodeAggregateId $nodeAggregateId,
    ) {
    }
}
