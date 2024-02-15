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

namespace Neos\ContentRepository\Core\Feature\NodeModification\Dto;

use Neos\ContentRepository\Core\NodeType\NodeType;

/**
 * "Raw" property values as saved in the event log // in projections.
 *
 * This means: each "value" must be a simple PHP data type.
 *
 * NOTE: if a value is set to NULL in SerializedPropertyValues, this means the key should be unset,
 * because we treat NULL and "not set" the same from an API perspective.
 *
 * @implements \IteratorAggregate<string,?SerializedPropertyValue>
 * @api used as part of commands/events
 */
final readonly class SerializedPropertyValues implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @param array<string,?SerializedPropertyValue> $values
     */
    private function __construct(
        public array $values
    ) {
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @param array<string,array{type:string,value:mixed}|SerializedPropertyValue|null> $propertyValues
     */
    public static function fromArray(array $propertyValues): self
    {
        $values = [];
        foreach ($propertyValues as $propertyName => $propertyValue) {
            if (!is_string($propertyName)) {
                throw new \InvalidArgumentException(sprintf('Invalid property name. Expected string, got: %s', get_debug_type($propertyName)), 1681326239);
            }
            if ($propertyValue === null) {
                // this case means we want to un-set a property.
                $values[$propertyName] = null;
            } elseif (is_array($propertyValue)) {
                $values[$propertyName] = SerializedPropertyValue::fromArray($propertyValue);
            } elseif ($propertyValue instanceof SerializedPropertyValue) {
                $values[$propertyName] = $propertyValue;
            } else {
                throw new \InvalidArgumentException(sprintf('Invalid property value. Expected instance of %s, got: %s', SerializedPropertyValue::class, get_debug_type($propertyValue)), 1546524480);
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

            if ($nodeType->hasReference($propertyName)) {
                throw new \InvalidArgumentException(
                    'References cannot be serialized; you need to use the SetNodeReferences command instead.',
                    1700154728
                );
            }

            $propertyTypeFromSchema = $nodeType->getPropertyType($propertyName);

            $values[$propertyName] = new SerializedPropertyValue($defaultValue, $propertyTypeFromSchema);
        }

        return new self($values);
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true));
    }

    public function merge(self $other): self
    {
        // here, we skip null values
        return new self(array_filter(
            array_merge($this->values, $other->values),
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
        return $this->values[$propertyName] ?? null;
    }

    /**
     * @return \Traversable<string,?SerializedPropertyValue>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->values;
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
            $values[$propertyName] = $propertyValue?->value;
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
