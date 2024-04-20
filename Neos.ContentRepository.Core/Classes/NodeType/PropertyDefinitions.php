<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\NodeType;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Closure;
use InvalidArgumentException;
use IteratorAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Traversable;

/**
 * @api
 * @implements IteratorAggregate<PropertyDefinition>
 */
final class PropertyDefinitions implements IteratorAggregate
{
    /**
     * @var array<string, PropertyDefinition>
     */
    private array $propertyDefinitions;

    private function __construct(PropertyDefinition ...$propertyDefinitions)
    {
        $this->propertyDefinitions = $propertyDefinitions;
    }

    /**
     * @param array<PropertyDefinition> $propertyDefinitions
     */
    public static function fromArray(array $propertyDefinitions): self
    {
        $propertyDefinitionByName = [];
        foreach ($propertyDefinitions as $index => $propertyDefinition) {
            $propertyDefinition instanceof PropertyDefinition || throw new InvalidArgumentException(sprintf('expected instance of %s, got: %s at index %s', PropertyDefinition::class, get_debug_type($propertyDefinition), $index), 1713542074);
            !array_key_exists($propertyDefinition->name->value, $propertyDefinitionByName) || throw new InvalidArgumentException(sprintf('Property definition "%s" is already registered at index %s', $propertyDefinition->name->value, $index), 1713542100);
            $propertyDefinitionByName[$propertyDefinition->name->value] = $propertyDefinition;
        }
        return new self(...$propertyDefinitionByName);
    }

    public function with(PropertyDefinition $propertyDefinition): self
    {
        if ($this->contain($propertyDefinition->name)) {
            throw new InvalidArgumentException(sprintf('Property definition "%s" is already registered', $propertyDefinition->name->value), 1713542132);
        }
        return new self(...[...$this->propertyDefinitions, $propertyDefinition->name->value => $propertyDefinition]);
    }

    public function get(PropertyName|string $propertyName): ?PropertyDefinition
    {
        if ($propertyName instanceof PropertyName) {
            $propertyName = $propertyName->value;
        }
        return $this->propertyDefinitions[$propertyName] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->propertyDefinitions === [];
    }

    public function contain(PropertyName|string $propertyName): bool
    {
        if ($propertyName instanceof PropertyName) {
            $propertyName = $propertyName->value;
        }
        return array_key_exists($propertyName, $this->propertyDefinitions);
    }

    /**
     * @param Closure(PropertyDefinition): bool $callback
     */
    public function filter(Closure $callback): self
    {
        return self::fromArray(array_filter($this->propertyDefinitions, $callback));
    }

    /**
     * @param Closure(PropertyDefinition): mixed $callback
     * @return array<mixed>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->propertyDefinitions);
    }

    public function getIterator(): Traversable
    {
        return yield from $this->propertyDefinitions;
    }

    /**
     * @return array<PropertyDefinition>
     */
    public function toArray(): array
    {
        return $this->propertyDefinitions;
    }
}
