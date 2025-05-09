<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

/**
 * @api part of {@see Subtree}
 * @implements \IteratorAggregate<Subtree>
 */
final readonly class Subtrees implements \IteratorAggregate, \Countable
{
    /** @var array<Subtree> */
    private array $items;

    private function __construct(
        Subtree ...$items
    ) {
        $this->items = $items;
    }

    /**
     * @internal
     */
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @internal
     * @param array<Subtree> $items
     */
    public static function fromArray(array $items): self
    {
        return new self(...$items);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
