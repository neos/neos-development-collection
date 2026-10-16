<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

final readonly class ReferenceTypeDeclaration
{
    private function __construct(
        public ReferenceTypeName $name,
        public ReferenceTypeNameList $superTypeNames,
    ) {
    }

    public static function create(
        ReferenceTypeName $name,
        ReferenceTypeNameList $superTypeNames,
    ): self {
       return new self(
           name: $name,
           superTypeNames: $superTypeNames,
       );
    }
}
