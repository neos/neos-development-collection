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
     * @param array<string,ReferenceTypeNameList> $declaredSubTypesNames indexed by parent name
     * @param array<string,ReferenceTypeNameList> $subTypeNames - recursive; indexed by ancestor name
     */
    private function __construct(
        private array $declaredSuperTypeNames,
        private array $superTypeNames,
        private array $declaredSubTypesNames,
        private array $subTypeNames,
        private ReferenceTypeDictionary $referenceTypes,
    ) {
    }

    public static function create(
        ReferenceTypeDeclarationDictionary $dictionary,
    ): self {
        $referenceTypes = [];
        $superTypeNames = [];
        $declaredSubTypeNames = [];
        $subTypeNames = [];
        foreach ($dictionary as $declaration) {
            if (!array_key_exists($declaration->name->value, $referenceTypes)) {
                $declaration->resolveReferenceTypeHierarchy(
                    dictionary: $dictionary,
                    hierarchySoFar: ReferenceTypeNameList::list(),
                    referenceTypes: $referenceTypes,
                    superTypeNames: $superTypeNames,
                    declaredSubTypeNames: $declaredSubTypeNames,
                    subTypeNames: $subTypeNames,
                );
            }
        }

        return new self(
            declaredSuperTypeNames: array_map(
                fn (ReferenceTypeDeclaration $declaration): ReferenceTypeNameList => $declaration->superTypeNames,
                iterator_to_array($dictionary),
            ),
            superTypeNames: array_map(
                fn (array $superTypeNamesForSubType): ReferenceTypeNameList
                    => ReferenceTypeNameList::list(...$superTypeNamesForSubType),
                $superTypeNames,
            ),
            declaredSubTypesNames: array_map(
                fn (array $declaredSubTypeNamesForSuperType): ReferenceTypeNameList
                    => ReferenceTypeNameList::list(...$declaredSubTypeNamesForSuperType),
                $declaredSubTypeNames,
            ),
            subTypeNames: array_map(
                fn (array $subTypeNamesForSuperType): ReferenceTypeNameList
                  => ReferenceTypeNameList::list(...$subTypeNamesForSuperType),
                $subTypeNames,
            ),
            referenceTypes: ReferenceTypeDictionary::create($referenceTypes),
        );
    }

    public static function createLazy(
        ReferenceTypeDeclarationDictionary $dictionary,
    ): self {
        $reflector = new \ReflectionClass(self::class);

        /** @phpstan-ignore method.notFound (¯\_(ツ)_/¯) */
        return $reflector->newLazyProxy(
            fn (): self => self::create($dictionary),
        );
    }

    public function getReferenceType(ReferenceTypeName $referenceTypeName): ?ReferenceType
    {
        return $this->referenceTypes->getReferenceType($referenceTypeName);
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
