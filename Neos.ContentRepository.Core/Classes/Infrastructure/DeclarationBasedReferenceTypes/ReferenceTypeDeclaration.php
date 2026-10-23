<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes;

use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\Exception\ReferenceTypeDeclarationIsInvalid;
use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\Exception\SuperTypeConflicts;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyDeclarationDictionary;
use Neos\ContentRepository\Core\ReferenceType\ReferenceType;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeConstraints;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;

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
        public ReferenceTypeConstraintsDeclaration $constraints,
        public PropertyDeclarationDictionary $properties,
    ) {
    }

    /**
     * @throws ReferenceTypeDeclarationIsInvalid
     */
    public static function create(
        ReferenceTypeName $name,
        bool $isAbstract,
        bool $isFinal,
        ReferenceTypeNameList $superTypeNames,
        ReferenceTypeConstraintsDeclaration $constraints,
        PropertyDeclarationDictionary $properties,
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
        );
    }

    /**
     * @param array<string,array<string,mixed>> $values
     */
    public static function fromArray(array $values, string $name): self
    {
        $name = ReferenceTypeName::fromString($name);
        if (!is_bool(($isAbstract = $values['abstract'] ?? false))) {
            throw new \InvalidArgumentException('Invalid value for bool "abstract", ' . get_debug_type($isAbstract) . ' given.');
        }
        if (!is_bool(($isFinal = $values['final'] ?? false))) {
            throw new \InvalidArgumentException('Invalid value for bool "final", ' . get_debug_type($isFinal) . ' given.');
        }

        return new self(
            name: $name,
            isAbstract: $isAbstract,
            isFinal: $isFinal,
            superTypeNames: ReferenceTypeNameList::fromStringArray(array_keys($values['superTypes'] ?? [])),
            constraints: ReferenceTypeConstraintsDeclaration::fromArray($values['constraints'] ?? []),
            properties: PropertyDeclarationDictionary::fromArray($values['properties'] ?? [], $name),
        );
    }

    /**
     * @param array<string,ReferenceType> $referenceTypes
     * @param array<string,array<string,ReferenceTypeName>> $superTypeNames
     * @param array<string,array<string,ReferenceTypeName>> $declaredSubTypeNames
     * @param array<string,array<string,ReferenceTypeName>> $subTypeNames
     * @param-out array<string,ReferenceType> $referenceTypes
     * @param-out array<string,array<string,ReferenceTypeName>> $superTypeNames
     * @param-out array<string,array<string,ReferenceTypeName>> $declaredSubTypeNames
     * @param-out array<string,array<string,ReferenceTypeName>> $subTypeNames
     * @throws ReferenceTypeDeclarationIsInvalid
     */
    public function resolveReferenceTypeHierarchy(
        ReferenceTypeDeclarationDictionary $dictionary,
        ReferenceTypeNameList $hierarchySoFar,
        array &$referenceTypes,
        array &$superTypeNames,
        array &$declaredSubTypeNames,
        array &$subTypeNames,
    ): void {
        $hierarchySoFar = $hierarchySoFar->append($this->name);
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
                    hierarchySoFar: $hierarchySoFar,
                    referenceTypes: $referenceTypes,
                    superTypeNames: $superTypeNames,
                    declaredSubTypeNames: $declaredSubTypeNames,
                    subTypeNames: $subTypeNames,
                );
            } else {
                if ($dictionary->getReferenceTypeDeclaration($superTypeName)?->isFinal) {
                    $finalSuperTypeNames[] = $superTypeName;
                    continue;
                }
            }
            foreach ($hierarchySoFar as $subTypeName) {
                $superTypeNames[$subTypeName->value] = array_merge(
                    $superTypeNames[$subTypeName->value] ?? [],
                    [$superTypeName->value => $superTypeName],
                    $superTypeNames[$superTypeName->value] ?? [],
                );
                $subTypeNames[$superTypeName->value][$subTypeName->value] = $subTypeName;
                foreach ($superTypeNames[$subTypeName->value] ?? [] as $ancestorSuperType) {
                    $subTypeNames[$ancestorSuperType->value][$subTypeName->value] = $subTypeName;
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

        $referenceTypes[$this->name->value] = $this->mergeToSubType(...$superTypes);
    }

    /**
     * @throws ReferenceTypeDeclarationIsInvalid
     */
    private function mergeToSubType(ReferenceType ...$superTypes): ReferenceType
    {
        if ($superTypes === []) {
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
        $superTypeConflicts = [];
        $previousSuperType = null;
        foreach ($superTypes as $superType) {
            $properties = $properties?->mergeToSubType($superType->properties, $superTypeConflicts, $superType->name, $previousSuperType?->name)
                ?: PropertyDeclarationDictionary::fromPropertyDictionary($superType->properties);
            $previousSuperType = $superType;
        }
        $properties = $properties->mergeToSubType($this->properties, $superTypeConflicts, $this->name);
        if ($superTypeConflicts !== []) {
            throw ReferenceTypeDeclarationIsInvalid::becauseItHasConflictingSuperTypes(
                referenceTypeName: $this->name,
                superTypeConflicts: SuperTypeConflicts::create($superTypeConflicts),
            );
        }

        return ReferenceType::create(
            name: $this->name,
            isAbstract: $this->isAbstract,
            constraints: ReferenceTypeConstraints::create(
                sourceNodeTypes: $constraints->sourceNodeTypes->toNodeTypeFilters(),
                targetNodeTypes: $constraints->targetNodeTypes->toNodeTypeFilters(),
            ),
            properties: $properties->toPropertyDictionary(),
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
            properties: $this->properties->toPropertyDictionary(),
        );
    }
}
