<?php

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;

/**
 * @api for the factory methods; NOT for the inner state.
 */
final class FindSubtreesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly NodeTypeConstraints $nodeTypeConstraints,
        public readonly int $maximumLevels,
    ) {
    }

    public static function nodeTypeConstraints(NodeTypeConstraints|string $nodeTypeConstraints): self
    {
        if (is_string($nodeTypeConstraints)) {
            $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($nodeTypeConstraints);
        }

        return new self($nodeTypeConstraints, 10000);
    }

    public function withMaximumLevels(int $maximumLevels): self
    {
        return new self($this->nodeTypeConstraints, $maximumLevels);
    }
}
