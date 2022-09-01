<?php

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * @api for the factory methods; NOT for the inner state.
 */
final class FindReferencedNodesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?ReferenceName $referenceName,
    ) {
    }

    public static function all(): self
    {
        return new self(null);
    }

    public static function referenceName(ReferenceName|string $referenceName): self
    {
        if (is_string($referenceName)) {
            $referenceName = ReferenceName::fromString($referenceName);
        }

        return new self($referenceName);
    }
}
