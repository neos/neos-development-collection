<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\ContentRepository\Core\Infrastructure\Declaration\Exception\ReferenceTypeDeclarationIsInvalid;
use Neos\ContentRepository\Core\Infrastructure\Declaration\Exception\SuperTypeConflicts;
use Neos\ContentRepository\Core\Infrastructure\Declaration\PropertyDeclarationDictionary;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\ReferenceType\ReferenceType;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeConstraints;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;

/**
 * @internal
 */
final readonly class ReferenceTypeDeclaration
{
    /**
     * @param ReferenceMetadataDeclarationDictionary<ReferenceMetadataDeclarationInterface> $metadata
     */
    private function __construct(
        public ReferenceTypeName $name,
        public bool $isAbstract,
        public bool $isFinal,
        public ReferenceTypeNameList $superTypeNames,
        public ReferenceTypeConstraintsDeclaration $constraints,
        public PropertyDeclarationDictionary $properties,
        public ReferenceMetadataDeclarationDictionary $metadata,
        private PropertyConverter $propertyConverter,
    ) {
    }

    /**
     * @param ReferenceMetadataDeclarationDictionary<ReferenceMetadataDeclarationInterface> $metadata
     * @throws ReferenceTypeDeclarationIsInvalid
     */
    public static function create(
        ReferenceTypeName $name,
        bool $isAbstract,
        bool $isFinal,
        ReferenceTypeNameList $superTypeNames,
        ReferenceTypeConstraintsDeclaration $constraints,
        PropertyDeclarationDictionary $properties,
        ReferenceMetadataDeclarationDictionary $metadata,
        PropertyConverter $propertyConverter,
    ): self {
        if ($isAbstract && $isFinal) {
            throw ReferenceTypeDeclarationIsInvalid::becauseItIsBothAbstractAndFinal($name);
        }

        return new self(
            name: $name,
            isAbstract: $isAbstract,
            isFinal: $isFinal,
            superTypeNames: $superTypeNames,
            constraints: $constraints,
            properties: $properties,
            metadata: $metadata,
            propertyConverter: $propertyConverter,
        );
    }

    /**
     * @param array<string,array<string,mixed>> $values
     * @param ReferenceMetadataDeclarationImplementationRegistry<ReferenceMetadataDeclarationInterface> $availableDeclarationClasses
     */
    public static function fromArray(
        array $values,
        string $name,
        ReferenceMetadataDeclarationImplementationRegistry $availableDeclarationClasses,
        PropertyConverter $propertyConverter,
    ): self {
        $name = ReferenceTypeName::fromString($name);
        if (!is_bool(($isAbstract = $values['abstract'] ?? false))) {
            throw new \InvalidArgumentException('Invalid value for bool "abstract", ' . get_debug_type($isAbstract) . ' given.');
        }
        if (!is_bool(($isFinal = $values['final'] ?? false))) {
            throw new \InvalidArgumentException('Invalid value for bool "final", ' . get_debug_type($isFinal) . ' given.');
        }

        $metadataDeclarations = [];
        foreach ($availableDeclarationClasses as $availableDeclarationClass) {
            $metadataDeclaration = $availableDeclarationClass::tryExtractFromArray($values);
            if ($metadataDeclaration) {
                $metadataDeclarations[] = $metadataDeclaration;
            }
        }

        return new self(
            name: $name,
            isAbstract: $isAbstract,
            isFinal: $isFinal,
            superTypeNames: ReferenceTypeNameList::fromStringArray(array_keys($values['superTypes'] ?? [])),
            constraints: ReferenceTypeConstraintsDeclaration::fromArray($values['constraints'] ?? []),
            properties: PropertyDeclarationDictionary::fromArray($values['properties'] ?? [], $name, $propertyConverter),
            metadata: ReferenceMetadataDeclarationDictionary::create(...$metadataDeclarations),
            propertyConverter: $propertyConverter,
        );
    }

    /**
     * @param array<string,ReferenceType> $referenceTypes
     * @param array<string,array<string,int>> $superTypeDistances
     * @param array<string,array<string,ReferenceTypeName>> $declaredSubTypeNames
     * @param array<string,array<string,int>> $subTypeDistances
     * @param-out array<string,ReferenceType> $referenceTypes
     * @param-out array<string,array<string,int>> $superTypeDistances
     * @param-out array<string,array<string,ReferenceTypeName>> $declaredSubTypeNames
     * @param-out array<string,array<string,int>> $subTypeDistances
     * @throws ReferenceTypeDeclarationIsInvalid
     */
    public function resolveReferenceTypeHierarchy(
        ReferenceTypeDeclarationDictionary $dictionary,
        MergedDeclaredReferenceTypeMembersDictionary $mergedMembersDictionary,
        ReferenceTypeNameList $hierarchySoFar,
        array &$referenceTypes,
        array &$superTypeDistances,
        array &$declaredSubTypeNames,
        array &$subTypeDistances,
        int $currentHierarchyLevel,
    ): void {
        $hierarchySoFar = $hierarchySoFar->append($this->name);
        $currentHierarchyLevel++;
        $unknownSuperTypeNames = [];
        $finalSuperTypeNames = [];
        $superTypes = [];
        foreach ($this->superTypeNames as $superTypeName) {
            if (!array_key_exists($superTypeName->value, $referenceTypes)) {
                $superTypeDeclaration = $dictionary->getReferenceTypeDeclaration($superTypeName);
                if (!$superTypeDeclaration) {
                    $unknownSuperTypeNames[] = $superTypeName;
                    continue;
                }
                if ($superTypeDeclaration->isFinal) {
                    $finalSuperTypeNames[] = $superTypeName;
                    continue;
                }
                $declaredSubTypeNames[$superTypeName->value][$this->name->value] = $this->name;
                foreach ($hierarchySoFar as $subTypeName) {
                    if ($hierarchySoFar->contains($superTypeName)) {
                        throw ReferenceTypeDeclarationIsInvalid::becauseItHasCircularSuperTypes(
                            referenceTypeName: $subTypeName,
                            circularSuperTypes: $hierarchySoFar->append($superTypeName),
                        );
                    }
                }
                $superTypeDeclaration->resolveReferenceTypeHierarchy(
                    dictionary: $dictionary,
                    mergedMembersDictionary: $mergedMembersDictionary,
                    hierarchySoFar: $hierarchySoFar,
                    referenceTypes: $referenceTypes,
                    superTypeDistances: $superTypeDistances,
                    declaredSubTypeNames: $declaredSubTypeNames,
                    subTypeDistances: $subTypeDistances,
                    currentHierarchyLevel: $currentHierarchyLevel + 1,
                );
            } else {
                if ($dictionary->getReferenceTypeDeclaration($superTypeName)?->isFinal) {
                    $finalSuperTypeNames[] = $superTypeName;
                    continue;
                }
            }
            foreach ($hierarchySoFar->reverse() as $subTypeLevel => $subTypeName) {
                $subTypeHierarchyLevel = $currentHierarchyLevel - $subTypeLevel;
                $superTypeDistances[$subTypeName->value] ??= [];
                if (
                    !array_key_exists($superTypeName->value, $superTypeDistances[$subTypeName->value])
                    || $superTypeDistances[$subTypeName->value][$superTypeName->value] > $subTypeHierarchyLevel
                ) {
                    $superTypeDistances[$subTypeName->value][$superTypeName->value] = $subTypeHierarchyLevel;
                    $subTypeDistances[$superTypeName->value][$subTypeName->value] = $subTypeHierarchyLevel;
                }
                foreach ($superTypeDistances[$superTypeName->value] ?? [] as $ancestorSuperTypeName => $distance) {
                    $descendantDistance = $subTypeHierarchyLevel + $distance;
                    if (
                        !array_key_exists($ancestorSuperTypeName, $superTypeDistances[$subTypeName->value])
                        || $superTypeDistances[$superTypeName->value][$ancestorSuperTypeName] > $descendantDistance
                    ) {
                        $superTypeDistances[$subTypeName->value][$ancestorSuperTypeName] = $descendantDistance;
                        $subTypeDistances[$ancestorSuperTypeName][$subTypeName->value] = $descendantDistance;
                    }
                }
            }
            $superTypes[] = $referenceTypes[$superTypeName->value];
        }
        if ($unknownSuperTypeNames !== []) {
            throw ReferenceTypeDeclarationIsInvalid::becauseItHasUnknownSuperTypes(
                $this->name,
                ReferenceTypeNameList::list(...$unknownSuperTypeNames)
            );
        }
        if ($finalSuperTypeNames !== []) {
            throw ReferenceTypeDeclarationIsInvalid::becauseItHasFinalSuperTypes(
                $this->name,
                ReferenceTypeNameList::list(...$finalSuperTypeNames)
            );
        }

        $referenceTypes[$this->name->value] = $this->mergeToSubType($mergedMembersDictionary, ...$superTypes);
    }

    /**
     * @throws ReferenceTypeDeclarationIsInvalid
     */
    private function mergeToSubType(
        MergedDeclaredReferenceTypeMembersDictionary $mergedMembersDictionary,
        ReferenceType ...$superTypes,
    ): ReferenceType {
        if ($superTypes === []) {
            $mergedMembersDictionary->add(MergedDeclaredReferenceTypeMembers::fromReferenceTypeDeclaration($this));
            return $this->toReferenceType();
        }

        /** @var ?ReferenceTypeConstraintsDeclaration $constraints */
        $constraints = null;
        foreach ($superTypes as $superType) {
            $constraints = $constraints?->mergeToSubTypes($superType->constraints)
                ?: ReferenceTypeConstraintsDeclaration::fromReferenceTypeConstraints($superType->constraints);
        }
        $constraints = $constraints->mergeToSubTypes($this->constraints);

        /** @var ?PropertyDeclarationDictionary $properties */
        $properties = null;
        /** @var ?ReferenceMetadataDeclarationDictionary<ReferenceMetadataDeclarationInterface> $metadata */
        $metadata = null;
        $superTypeConflicts = [];
        $previousSuperType = null;
        foreach ($superTypes as $superType) {
            $mergedSuperTypeMembers = $mergedMembersDictionary->getMergedDeclaredReferenceTypeMembers($superType->name)
                ?: throw new \RuntimeException('Space time continuum exception: somehow got a supertype without merged members, must not happen');
            $properties = $properties?->mergeToSubType($mergedSuperTypeMembers->properties, $superTypeConflicts, $superType->name, $this->propertyConverter, $previousSuperType?->name)
                ?: $mergedSuperTypeMembers->properties;
            $metadata = $metadata?->mergeToSubType($mergedSuperTypeMembers->metadata)
                ?: $mergedSuperTypeMembers->metadata;
            $previousSuperType = $superType;
        }
        $properties = $properties->mergeToSubType($this->properties, $superTypeConflicts, $this->name, $this->propertyConverter);
        if ($superTypeConflicts !== []) {
            throw ReferenceTypeDeclarationIsInvalid::becauseItHasConflictingSuperTypes(
                referenceTypeName: $this->name,
                superTypeConflicts: SuperTypeConflicts::create($superTypeConflicts),
            );
        }
        $mergedMembersDictionary->add(MergedDeclaredReferenceTypeMembers::create($this->name, $properties, $metadata));

        return ReferenceType::create(
            name: $this->name,
            isAbstract: $this->isAbstract,
            constraints: ReferenceTypeConstraints::create(
                sourceNodeTypes: $constraints->sourceNodeTypes->toNodeTypeFilters(),
                targetNodeTypes: $constraints->targetNodeTypes->toNodeTypeFilters(),
            ),
            properties: $properties->toPropertyDictionary(PropertyScope::SCOPE_NODE_AGGREGATE),
            metadata: $metadata->toReferenceMetadataDictionary(),
        );
    }

    private function toReferenceType(): ReferenceType
    {
        return ReferenceType::create(
            name: $this->name,
            isAbstract: $this->isAbstract,
            constraints: ReferenceTypeConstraints::create(
                sourceNodeTypes: $this->constraints->sourceNodeTypes->toNodeTypeFilters(),
                targetNodeTypes: $this->constraints->targetNodeTypes->toNodeTypeFilters(),
            ),
            properties: $this->properties->toPropertyDictionary(PropertyScope::SCOPE_NODE_AGGREGATE),
            metadata: $this->metadata->toReferenceMetadataDictionary(),
        );
    }
}
