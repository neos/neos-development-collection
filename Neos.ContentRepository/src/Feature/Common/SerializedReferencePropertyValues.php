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
 * A collection of SerializedPropertyValues collections, to be used in reference relations.
 *
 * @implements \IteratorAggregate<string,SerializedPropertyValues>
 */
final class SerializedReferencePropertyValues implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * SerializedPropertyValues collections, indexed by target node aggregate identifier
     *
     * @var array<string,SerializedPropertyValues>
     */
    private array $values;

    /**
     * @param array<string,SerializedPropertyValues> $values
     */
    private function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @param array<string,mixed> $referencePropertyValues
     */
    public static function fromArray(array $referencePropertyValues): self
    {
        return new self(array_map(
            fn (array $propertyValues): SerializedPropertyValues
                => SerializedPropertyValues::fromArray($propertyValues),
            $referencePropertyValues
        ));
    }

    public static function defaultFromNodeType(NodeType $nodeType, PropertyName $referenceName): self
    {
        $values = [];
        $referencePropertiesConfiguration
            = $nodeType->getFullConfiguration()['properties'][(string)$referenceName]['properties'];
        foreach ($referencePropertiesConfiguration as $referencePropertyName => $referencePropertyConfiguration) {
            if (is_string($referencePropertyName) && isset($referencePropertyConfiguration['defaultValue'])) {
                $type = $referencePropertyConfiguration['type'] ?? '';
                $values[$referencePropertyName] = match ($type) {
                    'DateTimeImmutable', 'DateTime'
                        => new \DateTimeImmutable($referencePropertyConfiguration['defaultValue']),
                    'reference', 'references' => throw new \InvalidArgumentException(
                            'Cannot use references as reference properties',
                            1655650930
                    ),
                    default => $referencePropertyConfiguration['defaultValue'],
                };
            }
        }
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
