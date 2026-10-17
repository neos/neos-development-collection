<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\ReferenceType\Exception\SuperTypeConflict;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;

/**
 * @api
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

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
