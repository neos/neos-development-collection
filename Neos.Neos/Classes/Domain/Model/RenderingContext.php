<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

final readonly class RenderingContext
{
    public function __construct(
        public Node $node,
        public Node $documentNode,
        public Node $siteNode
    ) {
    }

    public function toContextArray(): array
    {
        return [
            'node' => $this->node,
            'documentNode' => $this->documentNode,
            'site' => $this->siteNode
        ];
    }
}
