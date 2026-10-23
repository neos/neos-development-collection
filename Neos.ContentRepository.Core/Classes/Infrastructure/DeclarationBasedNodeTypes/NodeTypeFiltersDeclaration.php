<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\DeclarationBasedNodeTypes;

use Neos\ContentRepository\Core\NodeType\NodeTypeFilters;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\Utility\Arrays;

/**
 * @api
 */
final readonly class NodeTypeFiltersDeclaration implements \JsonSerializable
{
    /**
     * @param array<string,bool> $filteredNodeTypes indexed by node type name, value whether it is to be included or not
     */
    private function __construct(
        public bool $includeWildcard,
        public array $filteredNodeTypes,
    ) {
    }

    /**
     * @param array<string,bool> $filteredNodeTypes indexed by node type name, value whether it is to be included or not
     */
    public static function create(
        bool $includeWildcard,
        array $filteredNodeTypes,
    ): self {
        return new self(
            includeWildcard: $includeWildcard,
            filteredNodeTypes: $filteredNodeTypes,
        );
    }

    /**
     * @param array<string,bool> $values
     */
    public static function fromArray(array $values): self
    {
        if (array_key_exists('*', $values)) {
            $includeWildcard = $values['*'];
            unset($values['*']);
        } else {
            $includeWildcard = false;
        }

        return new self(
            includeWildcard: $includeWildcard,
            filteredNodeTypes: $values,
        );
    }

    public static function fromNodeTypeFilters(NodeTypeFilters $nodeTypeConstraints): self
    {
        $constrainedNodeTypes = [];
        foreach ($nodeTypeConstraints->explicitlyIncludedNodeTypeNames as $explicitlyIncludedNodeTypeName) {
            $constrainedNodeTypes[$explicitlyIncludedNodeTypeName->value] = true;
        }
        foreach ($nodeTypeConstraints->explicitlyExcludedNodeTypeNames as $explicitlyExcludedNodeTypeName) {
            $constrainedNodeTypes[$explicitlyExcludedNodeTypeName->value] = false;
        }

        return new self(
            includeWildcard: $nodeTypeConstraints->isWildcardAllowed,
            filteredNodeTypes: $constrainedNodeTypes,
        );
    }

    public function mergeToSubType(self|NodeTypeFilters $subTypeNodeTypeFilters): self
    {
        if ($subTypeNodeTypeFilters instanceof NodeTypeFilters) {
            $subTypeNodeTypeFilters = self::fromNodeTypeFilters($subTypeNodeTypeFilters);
        }

        return new self(
            includeWildcard: $subTypeNodeTypeFilters->includeWildcard,
            filteredNodeTypes: Arrays::arrayMergeRecursiveOverrule(
                $this->filteredNodeTypes,
                $subTypeNodeTypeFilters->filteredNodeTypes,
            ),
        );
    }

    public function toNodeTypeFilters(): NodeTypeFilters
    {
        $explicitlyIncludedNodeTypeNames = [];
        $explicitlyExcludedNodeTypeNames = [];
        foreach ($this->filteredNodeTypes as $nodeTypeName => $isAllowed) {
            if ($isAllowed) {
                $explicitlyIncludedNodeTypeNames[] = $nodeTypeName;
            } else {
                $explicitlyExcludedNodeTypeNames[] = $nodeTypeName;
            }
        }
        return NodeTypeFilters::create(
            isWildcardAllowed: $this->includeWildcard,
            includeAbstract: true,
            explicitlyIncludedNodeTypeNames: NodeTypeNames::fromStringArray($explicitlyIncludedNodeTypeNames),
            explicitlyExcludedNodeTypeNames: NodeTypeNames::fromStringArray($explicitlyExcludedNodeTypeNames),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
