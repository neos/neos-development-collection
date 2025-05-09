<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

/**
 * @api returned by {@see ContentSubgraphInterface::findSubtree()}
 */
final readonly class Subtree
{
    private function __construct(
        public int $level,
        public Node $node,
        public Subtrees $children
    ) {
    }

    /**
     * @internal
     */
    public static function create(
        int $level,
        Node $node,
        Subtrees $children
    ): self {
        return new self($level, $node, $children);
    }
}
