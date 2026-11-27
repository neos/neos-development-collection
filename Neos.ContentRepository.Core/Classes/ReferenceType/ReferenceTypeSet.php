<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
 * A set (unordered, distinct) of ReferenceType instances
 * @api
 * @implements \IteratorAggregate<string,ReferenceType> indexed by name to enforce distinctness
 */
final readonly class ReferenceTypeSet implements \IteratorAggregate
{
    /**
     * @param array<string,ReferenceType> $items
     */
    private function __construct(
        private array $items,
    ) {
    }

    public static function create(ReferenceType ...$set): self
    {
        $items = [];
        foreach ($set as $item) {
            $items[$item->name->value] = $item;
        }

        return new self($items);
    }

    public function union(self $other): self
    {
        return new self(array_merge(
            $this->items,
            $other->items,
        ));
    }

    public function hasElementNamed(ReferenceTypeName $referenceTypeName): bool
    {
        return array_key_exists($referenceTypeName->value, $this->items);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @template T
     * @param \Closure(ReferenceType $referenceTypeName): T $callback
     * @return array<string, T>
     */
    public function map(\Closure $callback): array
    {
        return array_map($callback, $this->items);
    }

    public function toNames(): ReferenceTypeNameSet
    {
        return ReferenceTypeNameSet::create(...$this->map(
            fn (ReferenceType $item): ReferenceTypeName => $item->name,
        ));
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
