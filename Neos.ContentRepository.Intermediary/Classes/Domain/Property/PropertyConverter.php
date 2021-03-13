<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain\Property;

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
use Neos\ContentRepository\Intermediary\Domain\Command\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValue;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\Flow\Annotations as Flow;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Serializer;

/**
 * @Flow\Scope("singleton")
 * @internal
 */
final class PropertyConverter
{
    private Serializer $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function serializePropertyValues(PropertyValuesToWrite $propertyValuesToWrite, NodeType $nodeType): SerializedPropertyValues
    {
        $serializedPropertyValues = [];

        foreach ($propertyValuesToWrite->getValues() as $propertyName => $propertyValue) {
            // WORKAROUND: $nodeType->getPropertyType() is missing the "initialize" call, so we need to trigger another method beforehand.
            $nodeType->getOptions();

            $propertyType = PropertyType::fromNodeTypeDeclaration($nodeType->getPropertyType($propertyName));

            try {
                $propertyValue = $this->serializer->normalize($propertyValue);
            } catch (NotEncodableValueException | NotNormalizableValueException $e) {
                throw new \RuntimeException('TODO: There was a problem serializing ' . get_class($propertyValue), 1594842314, $e);
            }

            $serializedPropertyValues[$propertyName] = new SerializedPropertyValue($propertyValue, (string)$propertyType);
        }

        return SerializedPropertyValues::fromArray($serializedPropertyValues);
    }

    public function deserializePropertyValue(SerializedPropertyValue $serializedPropertyValue)
    {
        if (is_null($serializedPropertyValue->getValue())) {
            return null;
        }

        return $this->serializer->denormalize($serializedPropertyValue->getValue(), $serializedPropertyValue->getType());
    }
}
