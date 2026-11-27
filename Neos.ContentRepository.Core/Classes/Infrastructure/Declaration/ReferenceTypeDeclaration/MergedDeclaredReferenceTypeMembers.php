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
    private function __construct(
        public ReferenceTypeName $name,
        public PropertyDeclarationDictionary $properties,
    ) {
    }

    public static function create(
        ReferenceTypeName $name,
        PropertyDeclarationDictionary $properties,
    ): self {
        return new self(
            name: $name,
            properties: $properties,
        );
    }

    public static function fromReferenceTypeDeclaration(ReferenceTypeDeclaration $declaration): self
    {
        return new self(
            name: $declaration->name,
            properties: $declaration->properties,
        );
    }
}
