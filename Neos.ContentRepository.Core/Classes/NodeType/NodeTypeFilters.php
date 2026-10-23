<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\NodeType;

/**
 * Filters to reduce the amount of available node types.
 * Usable for all kind of constraint checks on relations, e.g. children or references.
 *
 * @api
 */
final readonly class NodeTypeFilters
{
    private function __construct(
        public bool $isWildcardAllowed,
        public bool $includeAbstract,
        public NodeTypeNames $explicitlyIncludedNodeTypeNames,
        public NodeTypeNames $explicitlyExcludedNodeTypeNames,
    ) {
    }

    public static function create(
        bool $isWildcardAllowed,
        bool $includeAbstract,
        NodeTypeNames $explicitlyIncludedNodeTypeNames,
        NodeTypeNames $explicitlyExcludedNodeTypeNames,
    ): self {
        return new self(
            isWildcardAllowed: $isWildcardAllowed,
            includeAbstract: $includeAbstract,
            explicitlyIncludedNodeTypeNames: $explicitlyIncludedNodeTypeNames,
            explicitlyExcludedNodeTypeNames: $explicitlyExcludedNodeTypeNames,
        );
    }
}
