<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

/**
 * @api returned by {@see ContentSubgraphInterface}
 */
final readonly class Subtree
{
    /**
     * @param array<int,Subtree> $children
     */
    public function __construct(
        public int $level,
        public Node $node,
        public array $children
    ) {
    }
}
