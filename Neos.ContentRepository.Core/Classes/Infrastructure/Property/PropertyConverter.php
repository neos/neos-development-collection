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

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
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
                PropertyType::fromNodeTypeDeclaration(
                    $nodeType->getPropertyType($propertyName),
                    PropertyName::fromString($propertyName),
                    $nodeType->name
                ),
                $propertyValue
            );
        }

        return SerializedPropertyValues::fromArray($serializedPropertyValues);
    }

    public function serializePropertyValue(
        PropertyType $propertyType,
        mixed $propertyValue
    ): SerializedPropertyValue {
        if ($propertyValue === null) {
            // should not happen, as we must separate regular properties and unsets beforehand!
            throw new \RuntimeException(
                sprintf('Property type %s with value "null" cannot be serialized as unsets are treated differently.', $propertyType->value),
                1707578784
            );
        }

        try {
            $serializedPropertyValue = $this->serializer->normalize($propertyValue);
        } catch (NotEncodableValueException | NotNormalizableValueException $e) {
            // todo add custom exception class
            throw new \RuntimeException(
                sprintf('There was a problem serializing property type %s with value "%s".', $propertyType->value, get_debug_type($propertyValue)),
                1594842314,
                $e
            );
        }

        if ($serializedPropertyValue === null) {
            throw new \RuntimeException(
                sprintf('While serializing property type %s with value "%s" the serializer returned not allowed value "null".', $propertyType->value, get_debug_type($propertyValue)),
                1707578784
            );
        }

        return SerializedPropertyValue::create(
            $serializedPropertyValue,
            $propertyType->getSerializationType()
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
                PropertyType::fromNodeTypeDeclaration(
                    $declaredType,
                    PropertyName::fromString($propertyName),
                    $nodeType->name,
                ),
                $propertyValue
            );
        }

        return SerializedPropertyValues::fromArray($serializedPropertyValues);
    }

    public function deserializePropertyValue(SerializedPropertyValue $serializedPropertyValue): mixed
    {
        try {
            return $this->serializer->denormalize(
                $serializedPropertyValue->value,
                $serializedPropertyValue->type
            );
        } catch (NotNormalizableValueException $e) {
            throw new \RuntimeException(
                sprintf('TODO: There was a problem deserializing %s', json_encode($serializedPropertyValue)),
                1708416598,
                $e
            );
        }
    }
}
