<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

final readonly class NodeWithContentStreamId
{
    private function __construct(
        public NodeAggregateId $aggregateId,
        public Node $instance,
        public ContentStreamId $contentStreamId,
    ) {
    }

    public static function create(Node $node, ContentStreamId $contentStreamId): self
    {
        return new self($node->aggregateId, $node, $contentStreamId);
    }

    public function withNode(Node $node): self
    {
        return new self($node->aggregateId, $node, $this->contentStreamId);
    }
}
