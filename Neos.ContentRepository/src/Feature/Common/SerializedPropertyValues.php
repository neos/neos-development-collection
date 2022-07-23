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

namespace Neos\ContentRepository\Feature\Common;

use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;

/**
 * "Raw" property values as saved in the event log // in projections.
 *
 * This means: each "value" must be a simple PHP data type.
 *
 * NOTE: if a value is set to NULL in SerializedPropertyValues, this means the key should be unset,
 * because we treat NULL and "not set" the same from an API perspective.
 *
 * @implements \IteratorAggregate<string,?SerializedPropertyValue>
 */
final class SerializedPropertyValues implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array<string,?SerializedPropertyValue>
     */
    private array $values;

    /**
     * @var \ArrayIterator<string,?SerializedPropertyValue>
     */
    protected \ArrayIterator $iterator;

    /**
     * @param array<string,?SerializedPropertyValue> $values
     */
    private function __construct(array $values)
    {
        $this->values = $values;
        $this->iterator = new \ArrayIterator($this->values);
    }

    /**
     * @param array<string,mixed> $propertyValues
     */
    public static function fromArray(array $propertyValues): self
    {
        $values = [];
        foreach ($propertyValues as $propertyName => $propertyValue) {
            if ($propertyValue === null) {
                // this case means we want to un-set a property.
                $values[$propertyName] = null;
            } elseif (is_array($propertyValue)) {
                $values[$propertyName] = SerializedPropertyValue::fromArray($propertyValue);
            } elseif ($propertyValue instanceof SerializedPropertyValue) {
                $values[$propertyName] = $propertyValue;
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid property value. Expected instance of %s, got: %s',
                    SerializedPropertyValue::class,
                    is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)
                ), 1546524480);
            }
        }

        return new self($values);
    }

    public static function defaultFromNodeType(NodeType $nodeType): self
    {
        $values = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $defaultValue) {
            if ($defaultValue instanceof \DateTimeInterface) {
                $defaultValue = json_encode($defaultValue);
            }

            $propertyTypeFromSchema = $nodeType->getPropertyType($propertyName);
            self::assertTypeIsNoReference($propertyTypeFromSchema);

            $values[$propertyName] = new SerializedPropertyValue($defaultValue, $propertyTypeFromSchema);
        }

        return new self($values);
    }

    public static function defaultForReferenceFromFromNodeType(PropertyName $referenceName, NodeType $nodeType): self
    {
        $values = [];
        $referencePropertiesConfiguration
            = $nodeType->getFullConfiguration()['properties'][(string)$referenceName]['properties'];
        foreach ($referencePropertiesConfiguration as $referencePropertyName => $referencePropertyConfiguration) {
            if (is_string($referencePropertyName) && isset($referencePropertyConfiguration['defaultValue'])) {
                $propertyTypeFromSchema = $referencePropertyConfiguration['type'] ?? '';
                if ($propertyTypeFromSchema === '') {
                    continue;
                }
                self::assertTypeIsNoReference($propertyTypeFromSchema);

                $defaultValue = match ($propertyTypeFromSchema) {
                    'DateTimeImmutable', 'DateTime'
                        => json_encode(new \DateTimeImmutable($referencePropertyConfiguration['defaultValue'])),
                    default => $referencePropertyConfiguration['defaultValue'],
                };

                $values[$referencePropertyName] = new SerializedPropertyValue($defaultValue, $propertyTypeFromSchema);
            }
        }

        return new self($values);
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true));
    }

    private static function assertTypeIsNoReference(string $propertyTypeFromSchema): void
    {
        if ($propertyTypeFromSchema === 'reference' || $propertyTypeFromSchema === 'references') {
            throw new \RuntimeException(
                'TODO: references cannot be serialized; you need to use the SetNodeReferences command instead.'
            );
        }
    }

    public function merge(self $other): self
    {
        // here, we skip null values
        return new self(array_filter(
            array_merge($this->values, $other->getValues()),
            fn ($value) => $value !== null
        ));
    }

    /**
     * @return array<string,self>
     */
    public function splitByScope(NodeType $nodeType): array
    {
        $propertyValuesByScope = [];
        foreach ($this->values as $propertyName => $propertyValue) {
            $declaration = $nodeType->getProperties()[$propertyName]['scope'] ?? null;
            if (is_string($declaration)) {
                $scope = PropertyScope::from($declaration);
            } else {
                $scope = PropertyScope::SCOPE_NODE;
            }
            $propertyValuesByScope[$scope->value][$propertyName] = $propertyValue;
        }

        return array_map(
            fn(array $propertyValues): self => self::fromArray($propertyValues),
            $propertyValuesByScope
        );
    }

    public function propertyExists(string $propertyName): bool
    {
        return isset($this->values[$propertyName]);
    }

    public function getProperty(string $propertyName): ?SerializedPropertyValue
    {
        if (!isset($this->values[$propertyName])) {
            return null;
        }

        return $this->values[$propertyName];
    }

    /**
     * @return array<string,?SerializedPropertyValue>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return \ArrayIterator<string,?SerializedPropertyValue>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function count(): int
    {
        return count($this->values);
    }

    /**
     * @return array<string,mixed>
     */
    public function getPlainValues(): array
    {
        $values = [];
        foreach ($this->values as $propertyName => $propertyValue) {
            $values[$propertyName] = $propertyValue?->getValue();
        }

        return $values;
    }

    /**
     * @return array<string,?SerializedPropertyValue>
     */
    public function jsonSerialize(): array
    {
        return $this->values;
    }
}
