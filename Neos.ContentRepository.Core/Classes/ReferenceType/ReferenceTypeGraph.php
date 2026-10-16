<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

use Neos\ContentRepository\Core\ReferenceType\Exception\ReferenceTypeDeclarationIsInvalid;

/**
 * @api
 */
final class ReferenceTypeGraph
{
    /**
     * @param array<string,ReferenceTypeNameList> $superTypeNames indexed by child name
     * @param array<string,ReferenceTypeNameList> $subTypesNames indexed by parent name
     * @param array<string,?ReferenceType> $referenceTypes indexed by name, initialized as null and resolved lazily
     * @param array<string,ReferenceTypeDeclaration> $referenceTypeDeclarations indexed by name
     */
    private function __construct(
        public readonly array $superTypeNames,
        public readonly array $subTypesNames,
        public array $referenceTypes,
        public readonly array $referenceTypeDeclarations,
    ) {
    }

    /**
     * @param array<ReferenceTypeDeclaration> $referenceTypeDeclarations
     */
    public static function fromDeclarations(
        array $referenceTypeDeclarations,
    ): self {
        $indexedReferenceTypeDeclarations = [];
        foreach ($referenceTypeDeclarations as $referenceTypeDeclaration) {
            $indexedReferenceTypeDeclarations[$referenceTypeDeclaration->name->value] = $referenceTypeDeclaration;
        }
        $subTypes = [];
        $superTypes = [];
        $referenceTypes = [];
        foreach ($referenceTypeDeclarations as $referenceTypeDeclaration) {
            $unknownSuperTypeNames = [];
            $finalSuperTypeNames = [];
            foreach ($referenceTypeDeclaration->superTypeNames as $declaredSuperTypeName) {
                $declaration = $indexedReferenceTypeDeclarations[$declaredSuperTypeName->value] ?? null;
                if (!$declaration) {
                    $unknownSuperTypeNames[] =  $declaredSuperTypeName;
                } elseif ($declaration->isFinal) {
                    $finalSuperTypeNames[] = $declaredSuperTypeName;
                } else {
                    $subTypes[$declaredSuperTypeName->value][$referenceTypeDeclaration->name->value]
                        = $referenceTypeDeclaration->name;
                }
            }
            if ($unknownSuperTypeNames !== []) {
                throw ReferenceTypeDeclarationIsInvalid::becauseItHasUnknownSuperTypes(
                    $referenceTypeDeclaration->name,
                    ReferenceTypeNameList::list(...$unknownSuperTypeNames),
                );
            }
            if ($finalSuperTypeNames !== []) {
                throw ReferenceTypeDeclarationIsInvalid::becauseItHasFinalSuperTypes(
                    $referenceTypeDeclaration->name,
                    ReferenceTypeNameList::list(...$finalSuperTypeNames),
                );
            }
            $referenceTypes[$referenceTypeDeclaration->name->value] = null;
            $superTypes[$referenceTypeDeclaration->name->value] = $referenceTypeDeclaration->superTypeNames;
        }

        return new self(
            superTypeNames: $superTypes,
            subTypesNames: array_map(
                fn (array $items): ReferenceTypeNameList => ReferenceTypeNameList::list(...$items),
                $subTypes,
            ),
            referenceTypes: $referenceTypes,
            referenceTypeDeclarations: $indexedReferenceTypeDeclarations,
        );
    }

    public function getReferenceType(ReferenceTypeName $referenceTypeName): ?ReferenceType
    {
        if (!array_key_exists($referenceTypeName->value, $this->referenceTypes)) {
            return null;
        }

        return $this->referenceTypes[$referenceTypeName->value]
            ??= $this->initializeReferenceType($referenceTypeName);
    }

    public function getReferenceTypeDeclaration(ReferenceTypeName $referenceTypeName): ?ReferenceTypeDeclaration
    {
        return $this->referenceTypeDeclarations[$referenceTypeName->value] ?? null;
    }

    public function getSuperTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList
    {
        return $this->superTypeNames[$referenceTypeName->value] ?? ReferenceTypeNameList::list();
    }

    public function getSubTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList
    {
        return $this->subTypesNames[$referenceTypeName->value] ?? ReferenceTypeNameList::list();
    }

    private function initializeReferenceType(ReferenceTypeName $referenceTypeName): ReferenceType
    {
        $referenceTypeDeclaration = $this->referenceTypeDeclarations[$referenceTypeName->value];

        return ReferenceType::create(
            name: $referenceTypeDeclaration->name,
            properties: [],
        );
    }
}
