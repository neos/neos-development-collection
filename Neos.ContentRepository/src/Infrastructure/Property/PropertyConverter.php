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

namespace Neos\ContentRepository\Infrastructure\Property;

use Neos\ContentRepository\Feature\Common\Exception\PropertyTypeIsInvalid;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValue;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
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

        foreach ($propertyValuesToWrite->getValues() as $propertyName => $propertyValue) {
            $serializedPropertyValues[$propertyName] = $this->serializePropertyValue(
                $nodeType->getPropertyType($propertyName),
                PropertyName::fromString($propertyName),
                NodeTypeName::fromString($nodeType->getName()),
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
    ): ?SerializedPropertyValue {
        try {
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
        } catch (PropertyTypeIsInvalid $exception) {
            if ($exception->getCode() === 1630063406 && $propertyValue === null) {
                // undeclared property
                // $propertyValue == null and not defined in NodeTypes -> we want to unset $propertyName!
                return null;
            } else {
                throw $exception;
            }
        }
    }

    public function serializeReferencePropertyValues(
        PropertyValuesToWrite $propertyValuesToWrite,
        NodeType $nodeType,
        PropertyName $referenceName
    ): SerializedPropertyValues {
        $serializedPropertyValues = [];

        foreach ($propertyValuesToWrite->getValues() as $propertyName => $propertyValue) {
            $declaredType = $nodeType->getProperties()[(string)$referenceName]['properties'][$propertyName]['type'];

            $serializedPropertyValues[$propertyName] = $this->serializePropertyValue(
                $declaredType,
                PropertyName::fromString($propertyName),
                NodeTypeName::fromString($nodeType->getName()),
                $propertyValue
            );
        }

        return SerializedPropertyValues::fromArray($serializedPropertyValues);
    }

    public function deserializePropertyValue(SerializedPropertyValue $serializedPropertyValue): mixed
    {
        if (is_null($serializedPropertyValue->getValue())) {
            return null;
        }

        return $this->serializer->denormalize(
            $serializedPropertyValue->getValue(),
            $serializedPropertyValue->getType()
        );
    }
}
