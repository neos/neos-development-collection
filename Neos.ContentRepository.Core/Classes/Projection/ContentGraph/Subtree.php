<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Projection\ContentGraph;

/**
 * @api returned by {@see ContentSubgraphInterface}
 */
final class Subtree
{
    /**
     * @param Subtree[] $children
     */
    public function __construct(
        public readonly int $level,
        public readonly ?Node $node = null,
        public array $children = []
    ) {
    }

    /**
     * @internal
     */
    public function add(Subtree $subtree): void
    {
        $this->children[] = $subtree;
    }
}
