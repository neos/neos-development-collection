<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\SharedModel\Node\NodeTypeConstraintsDeclaration;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyDeclarationDictionary;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * @api
 */
final class ReferenceType
{
    private function __construct(
        public ReferenceTypeName $name,
        public bool $isAbstract,
        public bool $isFinal,
        public NodeTypeConstraintsDeclaration $sourceNodeTypes,
        public NodeTypeConstraintsDeclaration $targetNodeTypes,
        public PropertyDeclarationDictionary $properties,
    ) {
    }

    public static function create(
        ReferenceTypeName $name,
        bool $isAbstract,
        bool $isFinal,
        NodeTypeConstraintsDeclaration $sourceNodeTypes,
        NodeTypeConstraintsDeclaration $targetNodeTypes,
        PropertyDeclarationDictionary $properties,
    ): self {
        return new self(
            name: $name,
            isAbstract: $isAbstract,
            isFinal: $isFinal,
            sourceNodeTypes: $sourceNodeTypes,
            targetNodeTypes: $targetNodeTypes,
            properties: $properties,
        );
    }

    public static function fromDeclaration(
        ReferenceTypeDeclaration $declaration,
    ): self {
        return new self(
            name: $declaration->name,
            isAbstract: $declaration->isAbstract,
            isFinal: $declaration->isFinal,
            sourceNodeTypes: $declaration->sourceNodeTypes,
            targetNodeTypes: $declaration->targetNodeTypes,
            properties: $declaration->properties,
        );
    }

    public function hasProperty(PropertyName $propertyName): bool
    {
        return $this->properties->hasProperty($propertyName);
    }

    public function getPropertyType(PropertyName $propertyName): ?PropertyType
    {
        return $this->properties->getPropertyType($propertyName);
    }
}
