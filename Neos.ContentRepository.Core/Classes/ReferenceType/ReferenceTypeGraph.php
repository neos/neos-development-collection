<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
 * @api
 */
final readonly class ReferenceTypeGraph
{
    /**
     * @param array<string,ReferenceTypeNameList> $declaredSuperTypeNames - direct; indexed by child name
     * @param array<string,ReferenceTypeNameList> $superTypeNames - recursive; indexed by descendant name
     * @param array<string,ReferenceTypeNameList> $declaredSubTypesNames - indexed by parent name
     * @param array<string,ReferenceTypeNameList> $subTypeNames - recursive; indexed by ancestor name
     * @param array<string,ReferenceType> $referenceTypes - indexed by name
     */
    private function __construct(
        private array $declaredSuperTypeNames,
        private array $superTypeNames,
        private array $declaredSubTypesNames,
        private array $subTypeNames,
        private array $referenceTypes,
    ) {
    }

    /**
     * @param array<string,ReferenceTypeNameList> $declaredSuperTypeNames - direct; indexed by child name
     * @param array<string,ReferenceTypeNameList> $superTypeNames - recursive; indexed by descendant name
     * @param array<string,ReferenceTypeNameList> $declaredSubTypesNames - indexed by parent name
     * @param array<string,ReferenceTypeNameList> $subTypeNames - recursive; indexed by ancestor name
     * @param array<string,ReferenceType> $referenceTypes - indexed by name
     */
    public static function create(
        array $declaredSuperTypeNames,
        array $superTypeNames,
        array $declaredSubTypesNames,
        array $subTypeNames,
        array $referenceTypes,
    ): self {
        return new self(
            declaredSuperTypeNames: $declaredSuperTypeNames,
            superTypeNames: $superTypeNames,
            declaredSubTypesNames: $declaredSubTypesNames,
            subTypeNames: $subTypeNames,
            referenceTypes: $referenceTypes,
        );
    }

    public function getReferenceType(ReferenceTypeName $referenceTypeName): ?ReferenceType
    {
        return $this->referenceTypes[$referenceTypeName->value] ?? null;
    }

    public function getDeclaredSuperTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList
    {
        return $this->declaredSuperTypeNames[$referenceTypeName->value] ?? ReferenceTypeNameList::list();
    }

    public function getSuperTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList
    {
        return $this->superTypeNames[$referenceTypeName->value] ?? ReferenceTypeNameList::list();
    }

    public function getDeclaredSubTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList
    {
        return $this->declaredSubTypesNames[$referenceTypeName->value] ?? ReferenceTypeNameList::list();
    }

    public function getSubTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList
    {
        return $this->subTypeNames[$referenceTypeName->value] ?? ReferenceTypeNameList::list();
    }
}
