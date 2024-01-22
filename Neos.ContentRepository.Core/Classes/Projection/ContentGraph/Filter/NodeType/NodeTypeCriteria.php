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

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\Utility\Arrays;

/**
 * The list of node type criteria needed for various find() operations on the {@see ContentSubgraphInterface}.
 *
 * This DTO is a direct representation of the user's intent, so the filter strings are translated
 * as follows:
 * - Foo.Bar: `allowed: [Foo.Bar]; disallowed: []`
 * - Foo.Bar,Baz,!Other: `allowed: [Foo.Bar, Baz]; disallowed: [Other]`
 * - (empty): `allowed: []; disallowed: []`.
 *
 * This is usually created by the factory method {@see self::fromFilterString}.
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
 *       semantics. You can use the internal method {@see ExpandedNodeTypeCriteria::create}
 *       to take sub-nodes into account.
 *
 * @api
 */
final class NodeTypeCriteria
{
    private function __construct(
        public readonly NodeTypeNames $explicitlyAllowedNodeTypeNames,
        public readonly NodeTypeNames $explicitlyDisallowedNodeTypeNames
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

    public static function createWithAllowedNodeTypeNames(NodeTypeNames $nodeTypeNames): self
    {
        return new self($nodeTypeNames, NodeTypeNames::createEmpty());
    }

    public static function createWithDisallowedNodeTypeNames(NodeTypeNames $nodeTypeNames): self
    {
        return new self(NodeTypeNames::createEmpty(), $nodeTypeNames);
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

    public function toFilterString(): string
    {
        $parts = [];
        foreach ($this->explicitlyDisallowedNodeTypeNames as $nodeTypeName) {
            $parts[] = '!' . $nodeTypeName->value;
        }

        foreach ($this->explicitlyAllowedNodeTypeNames as $nodeTypeName) {
            $parts[] = $nodeTypeName->value;
        }

        return implode(',', $parts);
    }
}
