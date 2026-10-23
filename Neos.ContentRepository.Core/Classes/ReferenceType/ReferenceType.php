<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Property\Property;
use Neos\ContentRepository\Core\SharedModel\Property\PropertyDictionary;

/**
 * @api to be evaluated in userland
 */
final readonly class ReferenceType
{
    private function __construct(
        public ReferenceTypeName $name,
        public bool $isAbstract,
        public ReferenceTypeConstraints $constraints,
        public PropertyDictionary $properties,
    ) {
    }

    public static function create(
        ReferenceTypeName $name,
        bool $isAbstract,
        ReferenceTypeConstraints $constraints,
        PropertyDictionary $properties,
    ): self {
        return new self(
            name: $name,
            isAbstract: $isAbstract,
            constraints: $constraints,
            properties: $properties,
        );
    }

    public function hasProperty(PropertyName $propertyName): bool
    {
        return $this->properties->hasProperty($propertyName);
    }

    public function getProperty(PropertyName $propertyName): ?Property
    {
        return $this->properties->getProperty($propertyName);
    }

    public function getPropertyType(PropertyName $propertyName): ?PropertyType
    {
        return $this->properties->getPropertyType($propertyName);
    }
}
