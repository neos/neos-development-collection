<?php

declare(strict_types=1);

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
        return self::all()->withNodeTypeConstraints($nodeTypeConstraints);
    }

    public function with(
        NodeTypeConstraints|string $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): self {
        if (is_string($nodeTypeConstraints)) {
            $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($nodeTypeConstraints);
        }
        return new self(
            $nodeTypeConstraints ?? $this->nodeTypeConstraints,
            $limit ?? $this->limit,
            $offset ?? $this->offset,
        );
    }

    public function withNodeTypeConstraints(NodeTypeConstraints|string $nodeTypeConstraints): self
    {
        return $this->with(nodeTypeConstraints: $nodeTypeConstraints);
    }

    public function withPagination(int $limit, int $offset): self
    {
        return $this->with(limit: $limit, offset: $offset);
    }
}
