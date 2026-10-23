<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes;

use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedNodeTypes\NodeTypeFiltersDeclaration;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeConstraints;

/**
 * @internal
 */
final readonly class ReferenceTypeConstraintsDeclaration
{
    private function __construct(
        public NodeTypeFiltersDeclaration $sourceNodeTypes,
        public NodeTypeFiltersDeclaration $targetNodeTypes,
    ) {
    }

    public static function create(
        NodeTypeFiltersDeclaration $sourceNodeTypes,
        NodeTypeFiltersDeclaration $targetNodeTypes,
    ): self {
        return new self(
            sourceNodeTypes: $sourceNodeTypes,
            targetNodeTypes: $targetNodeTypes,
        );
    }

    /**
     * @param array<string,array<string,bool>> $values
     */
    public static function fromArray(array $values): self
    {
        return self::create(
            sourceNodeTypes: NodeTypeFiltersDeclaration::fromArray($values['sourceNodeTypes'] ?? []),
            targetNodeTypes: NodeTypeFiltersDeclaration::fromArray($values['targetNodeTypes'] ?? []),
        );
    }

    public static function fromReferenceTypeConstraints(ReferenceTypeConstraints $referenceTypeConstraints): self
    {
        return new self(
            sourceNodeTypes: NodeTypeFiltersDeclaration::fromNodeTypeFilters($referenceTypeConstraints->sourceNodeTypes),
            targetNodeTypes: NodeTypeFiltersDeclaration::fromNodeTypeFilters($referenceTypeConstraints->targetNodeTypes),
        );
    }

    public function mergeToSubTypes(self|ReferenceTypeConstraints $subTypeConstraints): self
    {
        $subTypeConstraints = $subTypeConstraints instanceof ReferenceTypeConstraints
            ? new self(
                sourceNodeTypes: NodeTypeFiltersDeclaration::fromNodeTypeFilters($subTypeConstraints->sourceNodeTypes),
                targetNodeTypes: NodeTypeFiltersDeclaration::fromNodeTypeFilters($subTypeConstraints->targetNodeTypes),
            )
            : $subTypeConstraints;

        return new self(
            sourceNodeTypes: $this->sourceNodeTypes->mergeToSubType($subTypeConstraints->sourceNodeTypes),
            targetNodeTypes: $this->targetNodeTypes->mergeToSubType($subTypeConstraints->targetNodeTypes),
        );
    }
}
