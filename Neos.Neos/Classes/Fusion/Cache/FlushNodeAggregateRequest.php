<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

final readonly class FlushNodeAggregateRequest
{
    private function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public NodeTypeName $nodeTypeName,
        public NodeAggregateIds $parentNodeAggregateIds,
    ) {
    }

    public static function create(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
        NodeTypeName $nodeTypeName,
        NodeAggregateIds $parentNodeAggregateIds
    ): self {
        return new self(
            $contentRepositoryId,
            $workspaceName,
            $nodeAggregateId,
            $nodeTypeName,
            $parentNodeAggregateIds
        );
    }
}
