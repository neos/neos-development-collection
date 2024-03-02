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
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;

/**
 * "Raw" property values as saved in the event log // in projections.
 *
 * This means: each "value" must be a simple PHP data type.
 *
 * @phpstan-import-type Value from SerializedPropertyValue
 *
 * @implements \IteratorAggregate<string,SerializedPropertyValue>
 * @api used as part of commands/events
 */
final readonly class SerializedPropertyValues implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @param array<string,SerializedPropertyValue> $values
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
     * @param array<string,array{type:string,value:Value}|SerializedPropertyValue> $propertyValues
     */
    public static function fromArray(array $propertyValues): self
    {
        $values = [];
        foreach ($propertyValues as $propertyName => $propertyValue) {
            if (!is_string($propertyName)) {
                throw new \InvalidArgumentException(sprintf('Invalid property name. Expected string, got: %s', get_debug_type($propertyName)), 1681326239);
            }
            if (is_array($propertyValue)) {
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

            $propertyTypeFromSchema = $nodeType->getPropertyType($propertyName);
            self::assertTypeIsNoReference($propertyTypeFromSchema);

            if ($defaultValue === null) {
                continue;
            }

            $values[$propertyName] = SerializedPropertyValue::create($defaultValue, $propertyTypeFromSchema);
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
        return new self(array_merge($this->values, $other->values));
    }

    public function unsetProperties(PropertyNames $propertyNames): self
    {
        $propertiesToUnsetMap = [];
        foreach ($propertyNames as $propertyName) {
            $propertiesToUnsetMap[$propertyName->value] = true;
        }
        return new self(array_diff_key($this->values, $propertiesToUnsetMap));
    }

    /**
     * @internal
     * @return array<string,self>
     */
    public function splitByScope(NodeType $nodeType): array
    {
        $propertyValuesByScope = [];
        foreach ($this->values as $propertyName => $propertyValue) {
            $scope = PropertyScope::tryFromDeclaration($nodeType, PropertyName::fromString($propertyName));
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
     * @return \Traversable<string,SerializedPropertyValue>
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
            $values[$propertyName] = $propertyValue->value;
        }

        return $values;
    }

    /**
     * @return array<string,SerializedPropertyValue|null>
     */
    public function jsonSerialize(): array
    {
        return $this->values;
    }
}
