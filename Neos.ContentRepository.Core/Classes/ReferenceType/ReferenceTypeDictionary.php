<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
 * @internal
 * @implements \IteratorAggregate<string,ReferenceType>
 */
final readonly class ReferenceTypeDictionary implements \IteratorAggregate
{
    /**
     * @param array<string,ReferenceType> $items indexed by name
     */
    private function __construct(
        private array $items,
    ) {
    }

    /**
     * @param array<string,ReferenceType> $items indexed by name
     */
    public static function create(array $items): self
    {
        return new self($items);
    }

    public function getReferenceType(ReferenceTypeName $name): ?ReferenceType
    {
        return $this->items[$name->value] ?? null;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
