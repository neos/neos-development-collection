<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Property;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\Dto\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValue;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\Flow\Annotations as Flow;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Serializer;

/**
 * @Flow\Scope("singleton")
 * @internal
 */
final class PropertyConversionService
{
    private Serializer $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    private static function assertPropertyTypeMatchesPropertyValue(string $propertyTypeFromSchema, $propertyValue, $propertyName)
    {
        if (is_object($propertyValue)) {
            if ($propertyValue === null) {
                return;
            }
            if ($propertyValue instanceof $propertyTypeFromSchema) {
                return;
            }

            throw new \RuntimeException('TODO: Property "' . $propertyName . '": type must match the type ' . $propertyTypeFromSchema . ', was ' . get_class($propertyValue));
        }
    }

    private static function assertTypeIsNoReference(string $propertyTypeFromSchema)
    {
        if ($propertyTypeFromSchema === 'reference' || $propertyTypeFromSchema === 'references') {
            throw new \RuntimeException('TODO: references cannot be serialized; you need to use the SetNodeReferences command instead.');
        }
    }

    public function serializePropertyValues(PropertyValuesToWrite $propertyValuesToWrite, NodeType $nodeType): SerializedPropertyValues
    {
        $serializedPropertyValues = [];

        foreach ($propertyValuesToWrite->getValues() as $propertyName => $propertyValue) {
            if ($propertyValue !== null) {
                // WORKAROUND: $nodeType->getPropertyType() is missing the "initialize" call, so we need to trigger another method beforehand.
                $nodeType->getProperties();

                $propertyTypeFromSchema = $nodeType->getPropertyType($propertyName);
                self::assertTypeIsNoReference($propertyTypeFromSchema);
                self::assertPropertyTypeMatchesPropertyValue($propertyTypeFromSchema, $propertyValue, $propertyName);

                try {
                    $propertyValue = $this->serializer->normalize($propertyValue);
                } catch (NotEncodableValueException $e) {
                    throw new \RuntimeException('TODO: There was a problem serializing ' . get_class($propertyValue), 1594842314, $e);
                }
                $serializedPropertyValues[$propertyName] = new SerializedPropertyValue($propertyValue, $propertyTypeFromSchema);
            } else {
                // $propertyValue == null -> we want to unset $propertyName!
                $serializedPropertyValues[$propertyName] = null;
            }
        }

        return SerializedPropertyValues::fromArray($serializedPropertyValues);
    }

    public function deserializePropertyValue(SerializedPropertyValue $serializedPropertyValue)
    {
        return $this->serializer->denormalize($serializedPropertyValue->getValue(), $serializedPropertyValue->getType());
    }
}
