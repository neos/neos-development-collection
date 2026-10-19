<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Property;

use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\Exception\SuperTypeConflict;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
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
        public PropertyMetadataDictionary $metadata,
    ) {
    }

    public static function create(
        PropertyName $name,
        PropertyType $type,
        PropertyMetadataDictionary $metadata,
    ): self {
        return new self(
            name: $name,
            type: $type,
            metadata: $metadata,
        );
    }

    public static function fromProperty(Property $property): self
    {
        return new self(
            name: $property->name,
            type: $property->type,
            metadata: $property->metadata,
        );
    }

    /**
     * @param array<string,list<SuperTypeConflict>> $superTypeConflicts
     * @param-out array<string,list<SuperTypeConflict>> $superTypeConflicts
     */
    public function mergeToSubType(
        self $subTypePropertySpecification,
        array &$superTypeConflicts,
        ReferenceTypeName $referenceTypeName,
        ?ReferenceTypeName $previousReferenceTypeName = null,
    ): self {
        if ($this->type->value !== $subTypePropertySpecification->type->value) {
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
                propertyType: $subTypePropertySpecification->type,
            );
        }
        return new self(
            $this->name,
            $this->type,
            $this->metadata->mergeToSubType($subTypePropertySpecification->metadata),
        );
    }

    public function toProperty(): Property
    {
        return Property::create(
            name: $this->name,
            type: $this->type,
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
