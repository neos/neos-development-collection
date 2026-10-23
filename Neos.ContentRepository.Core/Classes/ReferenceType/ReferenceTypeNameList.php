<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
 * A dense, 0-indexed list of reference type names, not necessarily unique
 *
 * @api
 * @implements \IteratorAggregate<int,ReferenceTypeName>
 */
final class ReferenceTypeNameList implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @param list<ReferenceTypeName> $items
     */
    private function __construct(
        public array $items,
    ) {
    }

    public static function list(ReferenceTypeName ...$items): self
    {
        return new self(\array_values($items));
    }

    /**
     * @param array<string> $values
     */
    public static function fromStringArray(array $values): self
    {
        return new self(array_map(
            fn (string $value): ReferenceTypeName => ReferenceTypeName::fromString($value),
            $values,
        ));
    }

    public function append(ReferenceTypeName ...$items): self
    {
        return new self(array_merge(
            $this->items,
            array_values($items),
        ));
    }

    public function contains(ReferenceTypeName $item): bool
    {
        return in_array($item, $this->items);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return array<int,string>
     */
    public function toStringArray(): array
    {
        return array_map(
            fn (ReferenceTypeName $item): string => $item->value,
            $this->items,
        );
    }

    /**
     * @return list<ReferenceTypeName>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
