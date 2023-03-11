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
            if (!isset($nodeType->getProperties()[$propertyName]) && $propertyValue === null) {
                // The property is undefined and the value null => we want to remove it, so we set it to null
                $serializedPropertyValues[$propertyName] = null;
            } else {
                $serializedPropertyValues[$propertyName] = $this->serializePropertyValue(
                    $nodeType->getPropertyType($propertyName),
                    PropertyName::fromString($propertyName),
                    $nodeType->name,
                    $propertyValue
                );
            }
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

        if ($propertyValue !== null) {
            try {
                $propertyValue = $this->serializer->normalize($propertyValue);
            } catch (NotEncodableValueException | NotNormalizableValueException $e) {
                throw new \RuntimeException(
                    'TODO: There was a problem serializing ' . get_class($propertyValue),
                    1594842314,
                    $e
                );
            }

            return new SerializedPropertyValue(
                $propertyValue,
                (string)$propertyType
            );
        } else {
            // $propertyValue == null and defined in node types (we have a resolved type)
            // -> we want to set the $propertyName to NULL
            return new SerializedPropertyValue(null, (string)$propertyType);
        }
    }

    public function serializeReferencePropertyValues(
        PropertyValuesToWrite $propertyValuesToWrite,
        NodeType $nodeType,
        ReferenceName $referenceName
    ): SerializedPropertyValues {
        $serializedPropertyValues = [];

        foreach ($propertyValuesToWrite->values as $propertyName => $propertyValue) {
            // reference properties are always completely overwritten,
            // so we don't need the node properties' unset option
            $declaredType = $nodeType->getProperties()[(string)$referenceName]['properties'][$propertyName]['type'];

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
        if (is_null($serializedPropertyValue->value)) {
            return null;
        }

        return $this->serializer->denormalize(
            $serializedPropertyValue->value,
            $serializedPropertyValue->type
        );
    }
}
