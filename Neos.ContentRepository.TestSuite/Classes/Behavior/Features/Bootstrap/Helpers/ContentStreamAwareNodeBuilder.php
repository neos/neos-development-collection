<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

final readonly class ContentStreamAwareNodeBuilder
{
    private function __construct(
        private ContentStreamId $contentStreamId
    ) {
    }

    public static function create(ContentStreamId $contentStreamId): self
    {
        return new self($contentStreamId);
    }

    public function buildNode(Node $node): ContentStreamAwareNode
    {
        return ContentStreamAwareNode::create($this->contentStreamId, $node);
    }
}
