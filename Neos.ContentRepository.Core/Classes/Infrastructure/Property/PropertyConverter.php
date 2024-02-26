<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Property;

use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Serializer;

/**
 * @internal
 */
final class PropertyConverter
{
    private Serializer $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function serializePropertyValues(
        PropertyValuesToWrite $propertyValuesToWrite,
        NodeType $nodeType
    ): SerializedPropertyValues {
        $serializedPropertyValues = [];

        foreach ($propertyValuesToWrite->values as $propertyName => $propertyValue) {
            $serializedPropertyValues[$propertyName] = $this->serializePropertyValue(
                $nodeType->getPropertyType($propertyName),
                PropertyName::fromString($propertyName),
                $nodeType->name,
                $propertyValue
            );
        }

        return SerializedPropertyValues::fromArray($serializedPropertyValues);
    }

    private function serializePropertyValue(
        string $declaredType,
        PropertyName $propertyName,
        NodeTypeName $nodeTypeName,
        mixed $propertyValue
    ): SerializedPropertyValue {
        $propertyType = PropertyType::fromNodeTypeDeclaration(
            $declaredType,
            $propertyName,
            $nodeTypeName
        );

        if ($propertyValue === null) {
            // should not happen, as we must separate regular properties and unsets beforehand!
            throw new \RuntimeException(
                sprintf('Property %s with value "null" cannot be serialized as unsets are treated differently.', $propertyName->value),
                1707578784
            );
        }

        try {
            $serializedPropertyValue = $this->serializer->normalize($propertyValue);
        } catch (NotEncodableValueException | NotNormalizableValueException $e) {
            // todo add custom exception class
            throw new \RuntimeException(
                sprintf('There was a problem serializing property %s with value "%s".', $propertyName->value, get_debug_type($propertyValue)),
                1594842314,
                $e
            );
        }

        if ($serializedPropertyValue === null) {
            throw new \RuntimeException(
                sprintf('While serializing property %s with value "%s" the serializer returned not allowed value "null".', $propertyName->value, get_debug_type($propertyValue)),
                1707578784
            );
        }

        return SerializedPropertyValue::create(
            $serializedPropertyValue,
            $propertyType->value
        );
    }

    public function serializeReferencePropertyValues(
        PropertyValuesToWrite $propertyValuesToWrite,
        NodeType $nodeType,
        ReferenceName $referenceName
    ): SerializedPropertyValues {
        $serializedPropertyValues = [];

        foreach ($propertyValuesToWrite->values as $propertyName => $propertyValue) {
            $declaredType = $nodeType->getProperties()[$referenceName->value]['properties'][$propertyName]['type'];

            $serializedPropertyValues[$propertyName] = $this->serializePropertyValue(
                $declaredType,
                PropertyName::fromString($propertyName),
                $nodeType->name,
                $propertyValue
            );
        }

        return SerializedPropertyValues::fromArray($serializedPropertyValues);
    }

    public function deserializePropertyValue(SerializedPropertyValue $serializedPropertyValue): mixed
    {
        return $this->serializer->denormalize(
            $serializedPropertyValue->value,
            $serializedPropertyValue->type
        );
    }
}
