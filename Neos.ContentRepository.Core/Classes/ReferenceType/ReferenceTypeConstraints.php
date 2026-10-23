<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

use Neos\ContentRepository\Core\NodeType\NodeTypeFilters;

/**
 * @api to be evaluated in userland
 */
final readonly class ReferenceTypeConstraints
{
    private function __construct(
        public NodeTypeFilters $sourceNodeTypes,
        public NodeTypeFilters $targetNodeTypes,
    ) {
    }

    public static function create(
        NodeTypeFilters $sourceNodeTypes,
        NodeTypeFilters $targetNodeTypes,
    ): self {
        return new self(
            sourceNodeTypes: $sourceNodeTypes,
            targetNodeTypes: $targetNodeTypes,
        );
    }
}
