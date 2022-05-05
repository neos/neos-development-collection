<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\SharedModel\NodeType;

/**
 * The list of node type constraints needed for various find() operations on the node tree.
 *
 * Never create an instance of this object by hand; rather use
 * {@see \Neos\ContentRepository\Domain\Factory\NodeTypeConstraintFactory}
 * @api
 */
final class NodeTypeConstraints
{
    public readonly NodeTypeNames $explicitlyAllowedNodeTypeNames;

    public readonly NodeTypeNames $explicitlyDisallowedNodeTypeNames;

    public function __construct(
        public readonly bool $isWildCardAllowed,
        NodeTypeNames $explicitlyAllowedNodeTypeNames = null,
        NodeTypeNames $explicitlyDisallowedNodeTypeNames = null
    ) {
        $this->explicitlyAllowedNodeTypeNames = $explicitlyAllowedNodeTypeNames ?: NodeTypeNames::createEmpty();
        $this->explicitlyDisallowedNodeTypeNames = $explicitlyDisallowedNodeTypeNames ?: NodeTypeNames::createEmpty();
    }

    public function matches(NodeTypeName $nodeTypeName): bool
    {
        // if $nodeTypeName is explicitly excluded, it is DENIED.
        foreach ($this->explicitlyDisallowedNodeTypeNames as $disallowed) {
            if ($nodeTypeName === $disallowed) {
                return false;
            }
        }

        // if $nodeTypeName is explicitly ALLOWED.
        foreach ($this->explicitlyAllowedNodeTypeNames as $allowed) {
            if ($nodeTypeName === $allowed) {
                return true;
            }
        }

        // otherwise, we return $wildcardAllowed.
        return $this->isWildCardAllowed;
    }

    /**
     * IMMUTABLE, returns a new instance
     */
    public function withExplicitlyDisallowedNodeType(NodeTypeName $nodeTypeName): self
    {
        return new NodeTypeConstraints(
            $this->isWildCardAllowed,
            $this->explicitlyAllowedNodeTypeNames,
            $this->explicitlyDisallowedNodeTypeNames->withAdditionalNodeTypeName($nodeTypeName)
        );
    }

    /**
     * return the legacy (pre-event-sourced) Node Type filter string looking like "Foo:Bar,!MyPackage:Exclude"
     * @deprecated
     */
    public function asLegacyNodeTypeFilterString(): string
    {
        $legacyParts = [];
        foreach ($this->explicitlyDisallowedNodeTypeNames as $nodeTypeName) {
            $legacyParts[] = '!' . $nodeTypeName;
        }

        foreach ($this->explicitlyAllowedNodeTypeNames as $nodeTypeName) {
            $legacyParts[] = (string)$nodeTypeName;
        }

        return implode(',', $legacyParts);
    }
}
