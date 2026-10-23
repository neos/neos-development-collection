<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Property;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * @implements \IteratorAggregate<string,Property>
 * @api
 */
final readonly class PropertyDictionary implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @param array<string,Property> $items
     */
    private function __construct(
        public array $items,
    ) {
    }

    /**
     * @param array<string,Property> $items
     */
    public static function create(array $items): self
    {
        return new self($items);
    }

    public function hasProperty(PropertyName $propertyName): bool
    {
        return array_key_exists($propertyName->value, $this->items);
    }

    public function getProperty(PropertyName $propertyName): ?Property
    {
        return $this->items[$propertyName->value] ?? null;
    }

    public function getPropertyType(PropertyName $propertyName): ?PropertyType
    {
        return ($this->items[$propertyName->value] ?? null)?->type;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
