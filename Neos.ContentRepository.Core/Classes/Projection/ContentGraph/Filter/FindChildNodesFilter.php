<?php

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;

/**
 * @api for the factory methods; NOT for the inner state.
 */
final class FindChildNodesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?NodeTypeConstraints $nodeTypeConstraints,
        public readonly ?int $limit,
        public readonly ?int $offset,
    ) {
    }

    public static function all(): self
    {
        return new self(null, null, null);
    }

    public static function nodeTypeConstraints(NodeTypeConstraints|string $nodeTypeConstraints): self
    {
        if (is_string($nodeTypeConstraints)) {
            $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($nodeTypeConstraints);
        }

        return new self($nodeTypeConstraints, null, null);
    }

    public function withPagination(int $limit, int $offset): self
    {
        return new self($this->nodeTypeConstraints, $limit, $offset);
    }
}
