<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\DeclarationBasedNodeTypes;

use Neos\ContentRepository\Core\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;

/**
 * @api
 */
final readonly class NodeTypeConstraintsDeclaration implements \JsonSerializable
{
    /**
     * @param array<string,bool> $constrainedNodeTypes indexed by node type name, value whether it is allowed or not
     */
    private function __construct(
        public bool $isWildcardAllowed,
        public array $constrainedNodeTypes,
    ) {
    }

    /**
     * @param array<string,bool> $constrainedNodeTypes indexed by node type name, value whether it is allowed or not
     */
    public static function create(
        bool $isWildcardAllowed,
        array $constrainedNodeTypes,
    ): self {
        return new self(
            isWildcardAllowed: $isWildcardAllowed,
            constrainedNodeTypes: $constrainedNodeTypes,
        );
    }

    /**
     * @param array<string,bool> $values
     */
    public static function fromArray(array $values): self
    {
        if (array_key_exists('*', $values)) {
            $isWildcardAllowed = $values['*'];
            unset($values['*']);
        } else {
            $isWildcardAllowed = false;
        }

        return new self(
            isWildcardAllowed: $isWildcardAllowed,
            constrainedNodeTypes: $values,
        );
    }

    public static function fromNodeTypConstraints(NodeTypeConstraints $nodeTypeConstraints): self
    {
        $constrainedNodeTypes = [];
        foreach ($nodeTypeConstraints->explicitlyAllowedNodeTypeNames as $explicitlyAllowedNodeTypeName) {
            $constrainedNodeTypes[$explicitlyAllowedNodeTypeName->value] = true;
        }
        foreach ($nodeTypeConstraints->explicitlyDisallowedNodeTypeNames as $explicitlyDisallowedNodeTypeName) {
            $constrainedNodeTypes[$explicitlyDisallowedNodeTypeName->value] = true;
        }

        return new self(
            isWildcardAllowed: $nodeTypeConstraints->isWildcardAllowed,
            constrainedNodeTypes: $constrainedNodeTypes,
        );
    }

    public function mergeToSubType(self|NodeTypeConstraints $subTypeNodeTypeConstraints): self
    {
        if ($subTypeNodeTypeConstraints instanceof NodeTypeConstraints) {
            $subTypeNodeTypeConstraints = self::fromNodeTypConstraints($subTypeNodeTypeConstraints);
        }
        return new self(
            isWildcardAllowed: $subTypeNodeTypeConstraints->isWildcardAllowed,
            constrainedNodeTypes: array_merge(
                $this->constrainedNodeTypes,
                $subTypeNodeTypeConstraints->constrainedNodeTypes,
            ),
        );
    }

    public function toNodeTypeConstraints(): NodeTypeConstraints
    {
        $explicitlyAllowedNodeTypeNames = [];
        $explicitlyDisallowedNodeTypeNames = [];
        foreach ($this->constrainedNodeTypes as $nodeTypeName => $isAllowed) {
            if ($isAllowed) {
                $explicitlyAllowedNodeTypeNames[] = $nodeTypeName;
            } else {
                $explicitlyDisallowedNodeTypeNames[] = $nodeTypeName;
            }
        }
        return NodeTypeConstraints::create(
            isWildcardAllowed: $this->isWildcardAllowed,
            includeAbstract: true,
            explicitlyAllowedNodeTypes: NodeTypeNames::fromStringArray($explicitlyAllowedNodeTypeNames),
            explicitlyDisallowedNodeTypes: NodeTypeNames::fromStringArray($explicitlyDisallowedNodeTypeNames),
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
