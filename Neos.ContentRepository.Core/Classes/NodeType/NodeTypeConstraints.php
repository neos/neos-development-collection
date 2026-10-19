<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\NodeType;

/**
 * Constraints to reduce the amount of available node types.
 * Usable for all kind of relations, e.g. children or references.
 *
 * @api
 */
final readonly class NodeTypeConstraints
{
    private function __construct(
        public bool $isWildcardAllowed,
        public bool $includeAbstract,
        public NodeTypeNames $explicitlyAllowedNodeTypeNames,
        public NodeTypeNames $explicitlyDisallowedNodeTypeNames,
    ) {
    }

    public static function create(
        bool $isWildcardAllowed,
        bool $includeAbstract,
        NodeTypeNames $explicitlyAllowedNodeTypes,
        NodeTypeNames $explicitlyDisallowedNodeTypes,
    ): self {
        return new self(
            isWildcardAllowed: $isWildcardAllowed,
            includeAbstract: $includeAbstract,
            explicitlyAllowedNodeTypeNames: $explicitlyAllowedNodeTypes,
            explicitlyDisallowedNodeTypeNames: $explicitlyDisallowedNodeTypes,
        );
    }
}
