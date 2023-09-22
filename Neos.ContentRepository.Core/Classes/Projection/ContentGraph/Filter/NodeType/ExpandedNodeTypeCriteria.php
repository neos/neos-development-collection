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

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;

/**
 * Implementation detail of {@see NodeTypeCriteria}, to be used inside implementations of ContentSubgraphInterface.
 *
 * @internal you want to use {@see NodeTypeCriteria} in public APIs.
 */
final class ExpandedNodeTypeCriteria
{
    private function __construct(
        public readonly bool $isWildCardAllowed,
        public readonly NodeTypeNames $explicitlyAllowedNodeTypeNames,
        public readonly NodeTypeNames $explicitlyDisallowedNodeTypeNames
    ) {
    }

    /**
     * @param array<string,bool> $nodeTypeDeclaration
     */
    public static function createFromNodeTypeDeclaration(
        array $nodeTypeDeclaration,
        NodeTypeManager $nodeTypeManager
    ): self {
        $wildCardAllowed = false;
        $explicitlyAllowedNodeTypeNames = [];
        $explicitlyDisallowedNodeTypeNames = [];
        foreach ($nodeTypeDeclaration as $constraintName => $allowed) {
            if ($constraintName === '*') {
                $wildCardAllowed = $allowed;
            } else {
                if ($allowed) {
                    $explicitlyAllowedNodeTypeNames[] = $constraintName;
                } else {
                    $explicitlyDisallowedNodeTypeNames[] = $constraintName;
                }
            }
        }

        return new self(
            $wildCardAllowed,
            self::expandByIncludingSubNodeTypes(
                NodeTypeNames::fromStringArray($explicitlyAllowedNodeTypeNames),
                $nodeTypeManager
            ),
            self::expandByIncludingSubNodeTypes(
                NodeTypeNames::fromStringArray($explicitlyDisallowedNodeTypeNames),
                $nodeTypeManager
            )
        );
    }

    public static function allowAll(): self
    {
        return new self(
            true,
            NodeTypeNames::createEmpty(),
            NodeTypeNames::createEmpty(),
        );
    }

    public static function create(NodeTypeCriteria $nodeTypeCriteria, NodeTypeManager $nodeTypeManager): self
    {
        // in case there are no filters, we fall back to allowing every node type.
        // Furthermore, if there are only negated filters,
        // we also fall back to allowing every node type (when the excludelist does not match)
        $nodeTypeCriteriaEmpty = $nodeTypeCriteria->explicitlyAllowedNodeTypeNames->isEmpty()
            && $nodeTypeCriteria->explicitlyDisallowedNodeTypeNames->isEmpty();
        $onlyNegatedFilters = $nodeTypeCriteria->explicitlyAllowedNodeTypeNames->isEmpty()
            && !$nodeTypeCriteria->explicitlyDisallowedNodeTypeNames->isEmpty();
        $wildcardAllowed = $nodeTypeCriteriaEmpty || $onlyNegatedFilters;

        return new self(
            $wildcardAllowed,
            self::expandByIncludingSubNodeTypes(
                $nodeTypeCriteria->explicitlyAllowedNodeTypeNames,
                $nodeTypeManager
            ),
            self::expandByIncludingSubNodeTypes(
                $nodeTypeCriteria->explicitlyDisallowedNodeTypeNames,
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

    public function toFilterString(): string
    {
        $parts = [];
        if ($this->isWildCardAllowed) {
            $parts[] = '*';
        }

        foreach ($this->explicitlyDisallowedNodeTypeNames as $nodeTypeName) {
            $parts[] = '!' . $nodeTypeName->value;
        }

        foreach ($this->explicitlyAllowedNodeTypeNames as $nodeTypeName) {
            $parts[] = $nodeTypeName->value;
        }

        return implode(',', $parts);
    }
}
