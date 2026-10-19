<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Property;

use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\Exception\PropertyDeclarationIsInvalid;
use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\Exception\SuperTypeConflict;
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
        public PropertyType $type,
        public mixed $defaultValue,
        public PropertyMetadataDictionary $metadata,
    ) {
    }

    /**
     * @throws PropertyDeclarationIsInvalid
     */
    public static function create(
        PropertyName $name,
        PropertyType $type,
        mixed $defaultValue,
        PropertyMetadataDictionary $metadata,
        ReferenceTypeName|NodeTypeName|null $declaringType,
    ): self {
        if (!$type->isMatchedBy($defaultValue)) {
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
            defaultValue: $defaultValue,
            metadata: $metadata,
        );
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values, string $name, ReferenceTypeName|NodeTypeName $declaringType): self
    {
        $name = PropertyName::fromString($name);
        $type = $declaringType instanceof ReferenceTypeName
            ? PropertyType::fromReferenceTypeDeclaration($values['type'] ?? '', $name, $declaringType)
            : PropertyType::fromNodeTypeDeclaration($values['type'] ?? '', $name, $declaringType);
        $defaultValue = $values['defaultValue'] ?? null;
        unset($values['type']);
        unset($values['defaultValue']);

        return self::create(
            name: $name,
            type: $type,
            defaultValue: $defaultValue,
            metadata: PropertyMetadataDictionary::create([
                'genericArray' => GenericArrayPropertyMetadata::create($values),
            ]),
            declaringType: $declaringType,
        );
    }

    public static function fromProperty(Property $property): self
    {
        return new self(
            name: $property->name,
            type: $property->type,
            defaultValue: $property->defaultValue,
            metadata: $property->metadata,
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
        if ($this->type->value !== $subTypePropertyDeclaration->type->value) {
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
            type: $this->type,
            defaultValue: $this->defaultValue ?: $subTypePropertyDeclaration->defaultValue,
            metadata: $this->metadata->mergeToSubType($subTypePropertyDeclaration->metadata),
            declaringType: $referenceTypeName,
        );
    }

    public function toProperty(): Property
    {
        return Property::create(
            name: $this->name,
            type: $this->type,
            defaultValue: $this->defaultValue,
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
