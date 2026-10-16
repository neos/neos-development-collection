<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

use Neos\ContentRepository\Core\ReferenceType\Exception\ReferenceTypeDeclarationIsInvalid;

/**
 * @internal
 */
final readonly class ReferenceTypeDeclaration
{
    private function __construct(
        public ReferenceTypeName $name,
        public bool $isAbstract,
        public bool $isFinal,
        public ReferenceTypeNameList $superTypeNames,
    ) {
    }

    public static function create(
        ReferenceTypeName $name,
        bool $isAbstract,
        bool $isFinal,
        ReferenceTypeNameList $superTypeNames,
    ): self {
        if ($isAbstract && $isFinal) {
            throw ReferenceTypeDeclarationIsInvalid::becauseItIsBothAbstractAndFinal($name);
        }

        return new self(
            name: $name,
            isAbstract: $isAbstract,
            isFinal: $isFinal,
            superTypeNames: $superTypeNames,
        );
    }
}
