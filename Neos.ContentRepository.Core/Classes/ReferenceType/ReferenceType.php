<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Property\Property;
use Neos\ContentRepository\Core\SharedModel\Property\PropertyDictionary;

/**
 * @api to be evaluated in userland
 */
final class ReferenceType
{
    private function __construct(
        public ReferenceTypeName $name,
        public bool $isAbstract,
        public bool $isFinal,
        public NodeTypeConstraints $sourceNodeTypeConstraints,
        public NodeTypeConstraints $targetNodeTypeConstraints,
        public PropertyDictionary $properties,
    ) {
    }

    public static function create(
        ReferenceTypeName $name,
        bool $isAbstract,
        bool $isFinal,
        NodeTypeConstraints $sourceNodeTypeConstraints,
        NodeTypeConstraints $targetNodeTypeConstraints,
        PropertyDictionary $properties,
    ): self {
        return new self(
            name: $name,
            isAbstract: $isAbstract,
            isFinal: $isFinal,
            sourceNodeTypeConstraints: $sourceNodeTypeConstraints,
            targetNodeTypeConstraints: $targetNodeTypeConstraints,
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
