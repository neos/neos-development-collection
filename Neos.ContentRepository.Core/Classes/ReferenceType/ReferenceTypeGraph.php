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
     * @param array<string,ReferenceTypeDistances> $superTypeDistances - recursive; indexed by descendant name
     * @param array<string,ReferenceTypeNameList> $declaredSubTypesNames - indexed by parent name
     * @param array<string,ReferenceTypeDistances> $subTypeDistances - recursive; indexed by ancestor name
     * @param array<string,ReferenceType> $referenceTypes - indexed by name
     */
    private function __construct(
        private array $declaredSuperTypeNames,
        private array $superTypeDistances,
        private array $declaredSubTypesNames,
        private array $subTypeDistances,
        private array $referenceTypes,
    ) {
    }

    /**
     * @param array<string,ReferenceTypeNameList> $declaredSuperTypeNames - direct; indexed by child name
     * @param array<string,ReferenceTypeDistances> $superTypeDistances - recursive; indexed by descendant name
     * @param array<string,ReferenceTypeNameList> $declaredSubTypesNames - indexed by parent name
     * @param array<string,ReferenceTypeDistances> $subTypeDistances - recursive; indexed by ancestor name
     * @param array<string,ReferenceType> $referenceTypes - indexed by name
     */
    public static function create(
        array $declaredSuperTypeNames,
        array $superTypeDistances,
        array $declaredSubTypesNames,
        array $subTypeDistances,
        array $referenceTypes,
    ): self {
        return new self(
            declaredSuperTypeNames: $declaredSuperTypeNames,
            superTypeDistances: $superTypeDistances,
            declaredSubTypesNames: $declaredSubTypesNames,
            subTypeDistances: $subTypeDistances,
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
        return ($this->superTypeDistances[$referenceTypeName->value] ?? null)?->toList() ?: ReferenceTypeNameList::list();
    }

    public function getDeclaredSubTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList
    {
        return $this->declaredSubTypesNames[$referenceTypeName->value] ?? ReferenceTypeNameList::list();
    }

    public function getSubTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList
    {
        return ($this->subTypeDistances[$referenceTypeName->value] ?? null)?->toList() ?: ReferenceTypeNameList::list();
    }

    public function getReferenceTypesMatchingConstraints(
        ReferenceTypeFilters $constraints,
    ): ReferenceTypeSet {
        if ($constraints->includeWildcard) {
            $candidateReferenceTypes = $constraints->includeAbstract
                ? $this->referenceTypes
                : array_filter($this->referenceTypes, fn (ReferenceType $referenceType): bool => !$referenceType->isAbstract);
        } else {
            $candidateReferenceTypes = [];
            foreach ($constraints->explicitlyIncludedReferenceTypeNames as $allowedReferenceTypeName) {
                $allowedReferenceType = $this->referenceTypes[$allowedReferenceTypeName->value] ?? null;
                if ($allowedReferenceType && ($constraints->includeAbstract || !$allowedReferenceType->isAbstract)) {
                    $candidateReferenceTypes[$allowedReferenceTypeName->value] = $allowedReferenceType;
                }
                foreach ($this->getSubTypeNames($allowedReferenceTypeName) as $allowedReferenceSubTypeName) {
                    $referenceSubType = $this->referenceTypes[$allowedReferenceSubTypeName->value] ?? null;
                    if ($referenceSubType && ($constraints->includeAbstract || !$referenceSubType->isAbstract)) {
                        $candidateReferenceTypes[$referenceSubType->name->value] = $referenceSubType;
                    }
                }
            }
        }

        return ReferenceTypeSet::create(...array_filter(
            $candidateReferenceTypes,
            fn (ReferenceType $referenceType): bool => $this->isCloserToExplicitlyAllowed(
                $referenceType->name,
                $constraints->explicitlyIncludedReferenceTypeNames,
                $constraints->explicitlyExcludedReferenceTypeNames,
            ),
        ));
    }

    private function isCloserToExplicitlyAllowed(
        ReferenceTypeName $referenceTypeName,
        ReferenceTypeNameSet $explicitlyAllowed,
        ReferenceTypeNameSet $explicitlyDisallowed,
    ): bool {
        if ($explicitlyDisallowed->isEmpty()) {
            return true;
        }

        $minimumDistanceToDisallowed = $this->calculateMinimumDistanceToSet($referenceTypeName, $explicitlyDisallowed);
        if ($minimumDistanceToDisallowed === null) {
            return true;
        }
        $minimumDistanceToAllowed = $this->calculateMinimumDistanceToSet($referenceTypeName, $explicitlyAllowed);
        if ($minimumDistanceToAllowed === null) {
            return false;
        }

        return $minimumDistanceToAllowed < $minimumDistanceToDisallowed;
    }

    /**
     * Calculates the minimum distance to any of the elements of a reference type name set - breadth first
     *
     * @return int|null
     *  * 0 if the reference type name is element of the set
     *  * 1 to n if the reference type is a subtype of one of the super types
     *  * null if the reference type is not a subtype of any of the super types
     */
    private function calculateMinimumDistanceToSet(
        ReferenceTypeName $referenceTypeName,
        ReferenceTypeNameSet $superTypeNames,
    ): ?int {
        if ($superTypeNames->isEmpty()) {
            return null;
        }
        if ($superTypeNames->hasElement($referenceTypeName)) {
            return 0;
        }
        $distances = [];
        foreach ($superTypeNames as $superTypeName) {
            $distance = ($this->superTypeDistances[$referenceTypeName->value] ?? null)?->getDistance($superTypeName);
            if ($distance === null) {
                // not a constrained subtype
                continue;
            }
            $distances[] = $distance;
        }
        if ($distances === []) {
            return null;
        }

        return min($distances);
    }
}
