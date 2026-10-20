<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
 * A set (unordered, distinct) of ReferenceTypeName instances
 * @api
 * @implements \IteratorAggregate<string,ReferenceTypeName> indexed by name to enforce distinctness
 */
final readonly class ReferenceTypeNameSet implements \IteratorAggregate
{
    /**
     * @param array<string,ReferenceTypeName> $items
     */
    private function __construct(
        private array $items,
    ) {
    }

    public static function create(ReferenceTypeName ...$set): self
    {
        $items = [];
        foreach ($set as $item) {
            $items[$item->value] = $item;
        }

        return new self($items);
    }

    public static function fromList(ReferenceTypeNameList $list): self
    {
        return self::create(...$list->items);
    }

    /**
     * @param array<string> $array
     */
    public static function fromStringArray(array $array): self
    {
        $items = [];
        foreach ($array as $value) {
            $items[$value] = ReferenceTypeName::fromString($value);
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

    public function hasElement(ReferenceTypeName $referenceTypeName): bool
    {
        return array_key_exists($referenceTypeName->value, $this->items);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @template T
     * @param \Closure(ReferenceTypeName $referenceTypeName): T $callback
     * @return array<string, T>
     */
    public function map(\Closure $callback): array
    {
        return array_map($callback, $this->items);
    }

    /**
     * @return array<string>
     */
    public function toStringArray(): array
    {
        return $this->map(fn (ReferenceTypeName $referenceTypeName) => $referenceTypeName->value);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
