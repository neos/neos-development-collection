<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
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

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return list<ReferenceTypeName>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
