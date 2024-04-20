<?php

namespace Neos\ContentRepository\Core\NodeType;

/**
 * @api
 */
final readonly class NodeTypeConstraints
{
    private function __construct(
        public NodeTypeNames $explicitlyAllowedNodeTypeNames,
        public NodeTypeNames $explicitlyDisallowedNodeTypeNames
    ) {
    }

    /**
     * We recommended to call this method with named arguments to better
     * understand the distinction between allowed and disallowed NodeTypeNames
     */
    public static function create(
        NodeTypeNames $allowed,
        NodeTypeNames $disallowed
    ): self {
        return new self($allowed, $disallowed);
    }

    public function isNodeTypeAllowed(NodeTypeName $name): bool
    {
        // TODO implement
    }
}
