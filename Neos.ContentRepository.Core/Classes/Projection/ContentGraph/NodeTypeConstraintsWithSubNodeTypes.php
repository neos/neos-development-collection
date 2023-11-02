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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;

/**
 * Implementation detail of {@see NodeTypeConstraints}, to be used inside implementations of ContentSubgraphInterface.
 *
 * @internal you want to use {@see NodeTypeConstraints} in public APIs.
 */
final class NodeTypeConstraintsWithSubNodeTypes
{
    private function __construct(
        public readonly bool $isWildCardAllowed,
        public readonly NodeTypeNames $explicitlyAllowedNodeTypeNames,
        public readonly NodeTypeNames $explicitlyDisallowedNodeTypeNames
    ) {
    }

    public static function create(NodeTypeConstraints $nodeTypeConstraints, NodeTypeManager $nodeTypeManager): self
    {
        // in case there are no filters, we fall back to allowing every node type.
        // Furthermore, if there are only negated filters,
        // we also fall back to allowing every node type (when the excludelist does not match)
        $nodeTypeConstraintsEmpty = $nodeTypeConstraints->explicitlyAllowedNodeTypeNames->isEmpty()
            && $nodeTypeConstraints->explicitlyDisallowedNodeTypeNames->isEmpty();
        $onlyNegatedFilters = $nodeTypeConstraints->explicitlyAllowedNodeTypeNames->isEmpty()
            && !$nodeTypeConstraints->explicitlyDisallowedNodeTypeNames->isEmpty();
        $wildcardAllowed = $nodeTypeConstraintsEmpty || $onlyNegatedFilters;

        return new self(
            $wildcardAllowed,
            self::expandByIncludingSubNodeTypes(
                $nodeTypeConstraints->explicitlyAllowedNodeTypeNames,
                $nodeTypeManager
            ),
            self::expandByIncludingSubNodeTypes(
                $nodeTypeConstraints->explicitlyDisallowedNodeTypeNames,
                $nodeTypeManager
            ),
        );
    }

    private static function expandByIncludingSubNodeTypes(
        NodeTypeNames $nodeTypeNames,
        NodeTypeManager $nodeTypeManager
    ): NodeTypeNames {
        $processedNodeTypeNames = [];
        foreach ($nodeTypeNames as $nodeTypeName) {
            $processedNodeTypeNames[$nodeTypeName->value] = $nodeTypeName;
            $subNodeTypes = $nodeTypeManager->getSubNodeTypes($nodeTypeName, true);
            foreach ($subNodeTypes as $subNodeType) {
                assert($subNodeType instanceof NodeType);
                $processedNodeTypeNames[$subNodeType->name->value] = $subNodeType->name;
            }
        }

        return NodeTypeNames::fromArray($processedNodeTypeNames);
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
}
