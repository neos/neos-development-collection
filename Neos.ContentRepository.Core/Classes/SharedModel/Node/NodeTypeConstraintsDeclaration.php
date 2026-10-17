<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

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

    public function mergeToSubType(self $subTypeNodeTypeConstraints): self
    {
        return new self(
            isWildcardAllowed: $subTypeNodeTypeConstraints->isWildcardAllowed,
            constrainedNodeTypes: array_merge(
                $this->constrainedNodeTypes,
                $subTypeNodeTypeConstraints->constrainedNodeTypes,
            ),
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
