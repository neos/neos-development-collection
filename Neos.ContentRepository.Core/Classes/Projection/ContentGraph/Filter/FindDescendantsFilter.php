<?php

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
        public readonly NodeTypeConstraints $nodeTypeConstraints,
        public readonly ?SearchTerm $searchTerm,
    ) {
    }

    public static function nodeTypeConstraints(NodeTypeConstraints|string $nodeTypeConstraints): self
    {
        if (is_string($nodeTypeConstraints)) {
            $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($nodeTypeConstraints);
        }

        return new self($nodeTypeConstraints, null);
    }

    public function withSearchTerm(SearchTerm|string $searchTerm): self
    {
        if (is_string($searchTerm)) {
            $searchTerm = SearchTerm::fulltext($searchTerm);
        }
        return new self($this->nodeTypeConstraints, $searchTerm);
    }
}
