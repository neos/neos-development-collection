<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration;

use Neos\ContentRepository\Core\Infrastructure\Declaration\PropertyDeclarationDictionary;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;

/**
 * @internal
 */
final readonly class MergedDeclaredReferenceTypeMembers
{
    /**
     * @param ReferenceMetadataDeclarationDictionary<ReferenceMetadataDeclarationInterface> $metadata
     */
    private function __construct(
        public ReferenceTypeName $name,
        public PropertyDeclarationDictionary $properties,
        public ReferenceMetadataDeclarationDictionary $metadata,
    ) {
    }

    /**
     * @param ReferenceMetadataDeclarationDictionary<ReferenceMetadataDeclarationInterface> $metadata
     */
    public static function create(
        ReferenceTypeName $name,
        PropertyDeclarationDictionary $properties,
        ReferenceMetadataDeclarationDictionary $metadata,
    ): self {
        return new self(
            name: $name,
            properties: $properties,
            metadata: $metadata,
        );
    }

    public static function fromReferenceTypeDeclaration(ReferenceTypeDeclaration $declaration): self
    {
        return new self(
            name: $declaration->name,
            properties: $declaration->properties,
            metadata: $declaration->metadata,
        );
    }
}
