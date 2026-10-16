<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
 * @api
 */
final class ReferenceTypeGraph
{
    /**
     * @var array<string,ReferenceTypeNameList> indexed by child name
     */
    private readonly array $superTypeNames;

    /**
     * @var array<string,ReferenceTypeNameList> indexed by parent name
     */
    private readonly array $subTypesNames;

    /**
     * @var array<string,?ReferenceType> indexed by name, initialized as null and resolved lazily
     */
    private array $referenceTypes;

    /**
     * @var array<string,ReferenceTypeDeclaration> indexed by name
     */
    private array $referenceTypeDeclarations;

    /**
     * @param \Closure(): array<ReferenceTypeDeclaration> $referenceTypeDeclarationLoader
     */
    public function __construct(
        \Closure $referenceTypeDeclarationLoader,
    ) {
        $subTypes = [];
        $superTypes = [];
        $referenceTypeDeclarations = [];
        $referenceTypes = [];
        foreach ($referenceTypeDeclarationLoader() as $referenceTypeDeclaration) {
            $referenceTypeDeclarations[$referenceTypeDeclaration->name->value] = $referenceTypeDeclaration;
            $referenceTypes[$referenceTypeDeclaration->name->value] = null;
            $superTypes[$referenceTypeDeclaration->name->value] = $referenceTypeDeclaration->superTypeNames;
            foreach ($referenceTypeDeclaration->superTypeNames as $superType) {
                $subTypes[$superType->value][$referenceTypeDeclaration->name->value] = $referenceTypeDeclaration->name;
            }
        }

        $this->superTypeNames = $superTypes;
        $this->subTypesNames = array_map(
            fn (array $items): ReferenceTypeNameList => ReferenceTypeNameList::list(...$items),
            $subTypes,
        );
        $this->referenceTypes = $referenceTypes;
        $this->referenceTypeDeclarations = $referenceTypeDeclarations;
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
