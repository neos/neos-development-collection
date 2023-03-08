<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;

/**
 * @api for the factory methods; NOT for the inner state.
 */
final class FindDescendantsFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?NodeTypeConstraints $nodeTypeConstraints,
        public readonly ?SearchTerm $searchTerm,
    ) {
    }

    public static function all(): self
    {
        return new self(null, null);
    }

    public static function nodeTypeConstraints(NodeTypeConstraints|string $nodeTypeConstraints): self
    {
        return self::all()->with(nodeTypeConstraints: $nodeTypeConstraints);
    }

    public function with(
        NodeTypeConstraints|string $nodeTypeConstraints = null,
        SearchTerm|string $searchTerm = null,
    ): self {
        if (is_string($nodeTypeConstraints)) {
            $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($nodeTypeConstraints);
        }
        if (is_string($searchTerm)) {
            $searchTerm = SearchTerm::fulltext($searchTerm);
        }
        return new self(
            $nodeTypeConstraints ?? $this->nodeTypeConstraints,
            $searchTerm ?? $this->searchTerm,
        );
    }

    public function withSearchTerm(SearchTerm|string $searchTerm): self
    {
        return $this->with(searchTerm: $searchTerm);
    }
}
