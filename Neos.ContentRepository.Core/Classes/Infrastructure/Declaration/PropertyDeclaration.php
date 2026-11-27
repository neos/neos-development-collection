<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Declaration;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Infrastructure\Declaration\Exception\PropertyDeclarationIsInvalid;
use Neos\ContentRepository\Core\Infrastructure\Declaration\Exception\SuperTypeConflict;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Property\GenericArrayPropertyMetadata;
use Neos\ContentRepository\Core\SharedModel\Property\Property;
use Neos\ContentRepository\Core\SharedModel\Property\PropertyMetadataDictionary;

/**
 * @internal
 */
final readonly class PropertyDeclaration implements \JsonSerializable
{
    private function __construct(
        public PropertyName $name,
        public PropertyType|Undeclared $type,
        public PropertyScope|Undeclared $scope,
        public mixed $defaultValue,
        public PropertyMetadataDictionary $metadata,
        private PropertyConverter $propertyConverter,
    ) {
    }

    /**
     * @throws PropertyDeclarationIsInvalid
     */
    public static function create(
        PropertyName $name,
        PropertyType|Undeclared $type,
        PropertyScope|Undeclared $scope,
        mixed $defaultValue,
        PropertyMetadataDictionary $metadata,
        ReferenceTypeName|NodeTypeName|null $declaringType,
        PropertyConverter $propertyConverter,
    ): self {
        if ($type !== Undeclared::INSTANCE) {
            if (class_exists($type->value) && !is_null($defaultValue) && !is_object($defaultValue)) {
                $defaultValue = $propertyConverter->deserializePropertyValue(SerializedPropertyValue::create(
                    value: $defaultValue,
                    type: $type->value,
                ));
            }
        }
        if (
            $type !== Undeclared::INSTANCE
            && $defaultValue !== Undeclared::INSTANCE
            && !$type->isMatchedBy($defaultValue)
        ) {
            throw PropertyDeclarationIsInvalid::becauseItsDefaultValueDoesNotMatchItsType(
                $name,
                $type,
                $defaultValue,
                $declaringType,
            );
        }
        return new self(
            name: $name,
            type: $type,
            scope: $scope,
            defaultValue: $defaultValue,
            metadata: $metadata,
            propertyConverter: $propertyConverter,
        );
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(
        array $values,
        string $name,
        ReferenceTypeName|NodeTypeName $declaringType,
        PropertyConverter $propertyConverter,
    ): self {
        $name = PropertyName::fromString($name);

        $type = array_key_exists('type', $values)
            ? (
                $declaringType instanceof ReferenceTypeName
                    ? PropertyType::fromReferenceTypeDeclaration($values['type'], $name, $declaringType)
                    : PropertyType::fromNodeTypeDeclaration($values['type'], $name, $declaringType)
            )
            : Undeclared::INSTANCE;
        unset($values['type']);

        $scope = array_key_exists('scope', $values)
            ? PropertyScope::from($values['scope'])
            : Undeclared::INSTANCE;
        unset($values['scope']);

        $defaultValue = array_key_exists('defaultValue', $values)
            ? $values['defaultValue']
            : Undeclared::INSTANCE;
        unset($values['defaultValue']);

        return self::create(
            name: $name,
            type: $type,
            scope: $scope,
            defaultValue: $defaultValue,
            metadata: PropertyMetadataDictionary::create([
                '_catchAll' => GenericArrayPropertyMetadata::create($values),
            ]),
            declaringType: $declaringType,
            propertyConverter: $propertyConverter,
        );
    }

    public static function fromProperty(Property $property, PropertyConverter $propertyConverter): self
    {
        return new self(
            name: $property->name,
            type: $property->type,
            scope: $property->scope,
            defaultValue: $property->defaultValue,
            metadata: $property->metadata,
            propertyConverter: $propertyConverter,
        );
    }

    /**
     * @param array<string,list<SuperTypeConflict>> $superTypeConflicts
     * @param-out array<string,list<SuperTypeConflict>> $superTypeConflicts
     */
    public function mergeToSubType(
        self $subTypePropertyDeclaration,
        array &$superTypeConflicts,
        ReferenceTypeName $referenceTypeName,
        ?ReferenceTypeName $previousReferenceTypeName = null,
    ): self {
        if (
            $this->type !== Undeclared::INSTANCE
            && $subTypePropertyDeclaration->type !== Undeclared::INSTANCE
            && $this->type->value !== $subTypePropertyDeclaration->type->value
        ) {
            if (!array_key_exists($this->name->value, $superTypeConflicts)) {
                $superTypeConflicts[$this->name->value][] = SuperTypeConflict::create(
                    // fallback should never happen, the first conflict can only occur
                    // when there is already a previous declaration
                    referenceTypeName: $previousReferenceTypeName ?: $referenceTypeName,
                    propertyType: $this->type,
                );
            }
            $superTypeConflicts[$this->name->value][] = SuperTypeConflict::create(
                referenceTypeName: $referenceTypeName,
                propertyType: $subTypePropertyDeclaration->type,
            );
        }

        return self::create(
            name: $this->name,
            type: $subTypePropertyDeclaration->type === Undeclared::INSTANCE
                ? $this->type
                : $subTypePropertyDeclaration->type,
            scope: $subTypePropertyDeclaration->scope === Undeclared::INSTANCE
                ? $this->scope
                : $subTypePropertyDeclaration->scope,
            defaultValue: $subTypePropertyDeclaration->defaultValue === Undeclared::INSTANCE
                ? $this->defaultValue
                : $subTypePropertyDeclaration->defaultValue,
            metadata: $this->metadata->mergeToSubType($subTypePropertyDeclaration->metadata),
            declaringType: $referenceTypeName,
            propertyConverter: $this->propertyConverter,
        );
    }

    public function toProperty(PropertyScope $defaultScope): Property
    {
        return Property::create(
            name: $this->name,
            type: $this->type === Undeclared::INSTANCE ? PropertyType::string() : $this->type,
            scope: $this->scope === Undeclared::INSTANCE ? $defaultScope : $this->scope,
            defaultValue: $this->defaultValue === Undeclared::INSTANCE ? null : $this->defaultValue,
            metadata: $this->metadata,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
