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

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\UnsetPropertyValue;
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
    ): SerializedPropertyValue|UnsetPropertyValue {
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
                    'TODO: There was a problem serializing ' . get_debug_type($propertyValue),
                    1594842314,
                    $e
                );
            }

            if ($propertyValue === null) {
                // todo or should we unset the property?
                throw new \RuntimeException(
                    'TODO: There was a problem serializing ' . get_debug_type($propertyValue) . '. The serializer returned null.',
                    1706797942
                );
            }

            return new SerializedPropertyValue(
                $propertyValue,
                $propertyType->value
            );
        }
        return UnsetPropertyValue::get();
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
