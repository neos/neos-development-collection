<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

final readonly class ContentStreamAwareNode
{
    private function __construct(
        public ContentStreamId $contentStreamId,
        public Node $nodeInstance,
        /** Alias for $currentNode->instance->aggregateId */
        public NodeAggregateId $aggregateId,
    ) {
    }

    public static function create(ContentStreamId $contentStreamId, Node $node): self
    {
        return new self($contentStreamId, $node, $node->aggregateId);
    }

    public function builder(): ContentStreamAwareNodeBuilder
    {
        return ContentStreamAwareNodeBuilder::create($this->contentStreamId);
    }
}
