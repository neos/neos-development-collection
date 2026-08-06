<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Property;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Psr\Clock\ClockInterface;

/**
 * @internal
 */
final readonly class NodeTypeDefaultPropertySerializer
{
    public function __construct(
        private PropertyConverter $propertyConverter,
        private ClockInterface $clock
    ) {
    }

    public function serializeFromNodeType(NodeType $nodeType): SerializedPropertyValues
    {
        $values = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $defaultValue) {
            $propertyType = PropertyType::fromNodeTypeDeclaration(
                $nodeType->getPropertyType($propertyName),
                PropertyName::fromString($propertyName),
                $nodeType->name
            );

            // The $defaultValue and $properlySerializedDefaultValue will likely equal, but in some cases diverge.
            // For example relative date time default values like "now" will herby be serialized to the current date.
            // Also, custom value objects might serialize slightly different, but more "correct"
            // (by for example adding default values for undeclared properties)
            // Additionally due the double conversion, we guarantee that a valid property converted exists at this time.
            if ($propertyType->isDate()) {
                if (!is_string($defaultValue)) {
                    throw new \RuntimeException(sprintf('Expected string as defaultValue for DateTime property of "%s" got: %s', $propertyName, get_debug_type($defaultValue)), 1783085240);
                }
                try {
                    $deserializedDefaultValue = $this->clock->now()->modify($defaultValue);
                } catch (\DateMalformedStringException $dateMalformedStringException) {
                    throw new \RuntimeException(sprintf('Invalid DateTime defaultValue "%s" for property "%s": %s', $defaultValue, $propertyName, $dateMalformedStringException->getMessage()), 1783085627, $dateMalformedStringException);
                }
            } else {
                $deserializedDefaultValue = $this->propertyConverter->deserializePropertyValue(
                    SerializedPropertyValue::create($defaultValue, $propertyType->getSerializationType())
                );
            }
            $properlySerializedDefaultValue = $this->propertyConverter->serializePropertyValue(
                $propertyType,
                $deserializedDefaultValue
            );
            $values[$propertyName] = $properlySerializedDefaultValue;
        }

        return SerializedPropertyValues::fromArray($values);
    }
}
