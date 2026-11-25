<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
 * @template T of ReferenceMetadataInterface
 * @implements \IteratorAggregate<class-string<T>,T>
 * @api
 */
final readonly class ReferenceMetadataDictionary implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @param array<class-string<T>,T> $items
     */
    private function __construct(
        public array $items,
    ) {
    }

    /**
     * @template I of ReferenceMetadataInterface
     * @param I ...$implementations
     * @return self<I>
     */
    public static function create(ReferenceMetadataInterface ...$implementations): self
    {
        $items = [];
        foreach ($implementations as $implementation) {
            $items[get_class($implementation)] = $implementation;
        }

        return new self($items);
    }

    /**
     * @param class-string<T> $itemClassName
     * @return T|null
     */
    public function getItem(string $itemClassName): ?ReferenceMetadataInterface
    {
        return $this->items[$itemClassName] ?? null;
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
