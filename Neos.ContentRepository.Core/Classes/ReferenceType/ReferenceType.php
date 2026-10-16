<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertySpecification;

/**
 * @api
 */
final class ReferenceType
{
    /**
     * @param array<string, PropertySpecification> $properties indexed by property name
     */
    private function __construct(
        public ReferenceTypeName $name,
        public array $properties,
    ) {
    }

    /**
     * @param array<string, PropertySpecification> $properties indexed by property name
     */
    public static function create(
        ReferenceTypeName $name,
        array $properties,
    ): self {
        return new self(
            name: $name,
            properties: $properties,
        );
    }

    public function hasProperty(PropertyName $propertyName): bool
    {
        return array_key_exists($propertyName->value, $this->properties);
    }

    public function getPropertyType(PropertyName $propertyName): ?PropertyType
    {
        return ($this->properties[$propertyName->value] ?? null)?->type;
    }
}
