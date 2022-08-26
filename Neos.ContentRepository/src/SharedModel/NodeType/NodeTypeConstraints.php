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

use Neos\Utility\Arrays;

/**
 * The list of node type constraints needed for various find() operations on the node tree.
 *
 * This DTO is a direct representation of the user's intent, so the filter strings are translated
 * as follows:
 * - Foo.Bar: `allowed: [Foo.Bar]; disallowed: []`
 * - Foo.Bar,Baz,!Other: `allowed: [Foo.Bar, Baz]; disallowed: [Other]`
 * - (empty): `allowed: []; disallowed: []`.
 *
 * This is usually created by the factory method {@see fromFilterString}.
 *
 *
 * ## Semantics
 *
 * 1) By specifying a node type, you allow or deny it *and all of its sub node types*
 *    according to the node type hierarchy.
 *
 * 2) Deny rules win over allow rules.
 *
 * 3) If no constraint is specified, everything is allowed.
 *
 * 4) If only deny rules are specified, everything is allowed **except** the specified node types
 *    and their sub node types.
 *
 * NOTE: Implementers of ContentSubgraphInterface need to take of implementing the above
 *       semantics. You can use the internal method {@see NodeTypeConstraintsWithSubNodeTypes::create}
 *       to take sub-nodes into account.
 *
 * @api
 */
final class NodeTypeConstraints
{
    private function __construct(
        public readonly NodeTypeNames $explicitlyAllowedNodeTypeNames,
        public readonly NodeTypeNames $explicitlyDisallowedNodeTypeNames
    ) {
    }

    public static function create(
        NodeTypeNames $explicitlyAllowedNodeTypeNames,
        NodeTypeNames $explicitlyDisallowedNodeTypeNames
    ): self {
        return new self($explicitlyAllowedNodeTypeNames, $explicitlyDisallowedNodeTypeNames);
    }

    public static function fromFilterString(string $serializedFilters): self
    {
        $explicitlyAllowedNodeTypeNames = [];
        $explicitlyDisallowedNodeTypeNames = [];

        $nodeTypeFilterParts = Arrays::trimExplode(',', $serializedFilters);
        foreach ($nodeTypeFilterParts as $nodeTypeFilterPart) {
            if (\mb_strpos($nodeTypeFilterPart, '!') === 0) {
                $negate = true;
                $nodeTypeFilterPart = \mb_substr($nodeTypeFilterPart, 1);
            } else {
                $negate = false;
            }

            if ($negate) {
                $explicitlyDisallowedNodeTypeNames[] = $nodeTypeFilterPart;
            } else {
                $explicitlyAllowedNodeTypeNames[] = $nodeTypeFilterPart;
            }
        }

        return new self(
            NodeTypeNames::fromStringArray($explicitlyAllowedNodeTypeNames),
            NodeTypeNames::fromStringArray($explicitlyDisallowedNodeTypeNames),
        );
    }

    /**
     * IMMUTABLE, returns a new instance
     */
    public function withAdditionalDisallowedNodeType(NodeTypeName $nodeTypeName): self
    {
        return new self(
            $this->explicitlyAllowedNodeTypeNames,
            $this->explicitlyDisallowedNodeTypeNames->withAdditionalNodeTypeName($nodeTypeName)
        );
    }

    /**
     * IMMUTABLE, returns a new instance
     */
    public function withAdditionalAllowedNodeType(NodeTypeName $nodeTypeName): self
    {
        return new self(
            $this->explicitlyAllowedNodeTypeNames->withAdditionalNodeTypeName($nodeTypeName),
            $this->explicitlyDisallowedNodeTypeNames
        );
    }

    public function __toString(): string
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
