<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\ReferenceType;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeDeclaration;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;

final readonly class ReferenceTypeDeclarationProperties
{
    public function __construct(
        public ReferenceTypeName $name,
        public bool $isAbstract,
        public bool $isFinal,
        public ReferenceTypeNameList $superTypeNames,
    ) {
    }

    public function equals(
        ?ReferenceTypeDeclaration $referenceTypeDeclaration,
    ): bool {
        return $referenceTypeDeclaration
            && $this->name === $referenceTypeDeclaration->name
            && $this->isAbstract === $referenceTypeDeclaration->isAbstract
            && $this->isFinal === $referenceTypeDeclaration->isFinal
            && $this->superTypeNames == $referenceTypeDeclaration->superTypeNames;
    }
}
