<?php

namespace Neos\ContentRepository\Core\NodeType;

/**
 * TODO, this thing
 * vs @see \Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria
 * vs @see \Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\ExpandedNodeTypeCriteria
 * vs @see \Neos\ContentRepository\Core\NodeType\ConstraintCheck
 *
 * see also https://github.com/neos/neos-development-collection/issues/4522
 *
 * @api
 */
final readonly class NodeTypeConstraints
{
    private function __construct(
        public NodeTypeNames $explicitlyAllowedNodeTypeNames,
        public NodeTypeNames $explicitlyDisallowedNodeTypeNames
    ) {
    }

    public static function createEmpty(): self
    {
        return new self(NodeTypeNames::createEmpty(), NodeTypeNames::createEmpty());
    }

    /**
     * We recommended to call this method with named arguments to better
     * understand the distinction between allowed and disallowed NodeTypeNames
     * todo add extra factories, see https://github.com/neos/neos-development-collection/pull/4658
     */
    public static function create(
        NodeTypeNames $allowed,
        NodeTypeNames $disallowed
    ): self {
        return new self($allowed, $disallowed);
    }

    public function isNodeTypeAllowed(NodeTypeName $name): bool
    {
        // if $nodeTypeName is explicitly excluded, it is DENIED.
        foreach ($this->explicitlyDisallowedNodeTypeNames as $disallowed) {
            if ($name === $disallowed) {
                return false;
            }
        }

        // if $nodeTypeName is explicitly ALLOWED.
        foreach ($this->explicitlyAllowedNodeTypeNames as $allowed) {
            if ($name === $allowed) {
                return true;
            }
        }

        // todo (re)add wildcard support??
        return true;
    }
}
