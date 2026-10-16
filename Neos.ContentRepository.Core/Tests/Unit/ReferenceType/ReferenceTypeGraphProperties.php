<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\ReferenceType;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeDeclaration;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;

final readonly class ReferenceTypeGraphProperties
{
    /**
     * @param array<string,ReferenceTypeNameList> $superTypeNames indexed by child name
     * @param array<string,ReferenceTypeNameList> $subTypesNames indexed by parent name
     * @param array<string,null> $referenceTypes indexed by name
     * @param array<string,ReferenceTypeDeclaration> $referenceTypeDeclarations indexed by name
     */
    public function __construct(
        public array $superTypeNames,
        public array $subTypesNames,
        public array $referenceTypes,
        public array $referenceTypeDeclarations,
    ) {
    }

    public function equals(
        ?ReferenceTypeGraph $referenceTypeGraph,
    ): bool {
        return $referenceTypeGraph
            && $this->superTypeNames === $referenceTypeGraph->superTypeNames
            && $this->subTypesNames === $referenceTypeGraph->subTypesNames
            && $this->referenceTypes === $referenceTypeGraph->referenceTypes
            && $this->referenceTypeDeclarations == $referenceTypeGraph->referenceTypeDeclarations;
    }
}
