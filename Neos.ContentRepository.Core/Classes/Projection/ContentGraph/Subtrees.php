<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

/**
 * An immutable, type-safe collection of Subtree objects
 *
 * @implements \IteratorAggregate<int,Subtree>
 *
 * @api returned by {@see ContentSubgraphInterface}
 */
final class Subtrees implements \IteratorAggregate, \Countable
{
    /**
     * @var array<int,Subtree>
     */
    private array $subtrees;

    /**
     * @param iterable<int,Subtree> $collection
     */
    private function __construct(iterable $collection)
    {
        $subtrees = [];
        foreach ($collection as $item) {
            if (!$item instanceof Subtree) {
                throw new \InvalidArgumentException(
                    'Subtrees can only consist of ' . Subtree::class . ' objects.',
                    1662037183
                );
            }
            $subtrees[] = $item;
        }

        $this->subtrees = $subtrees;
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @param Subtree[] $subtrees
     * @return static
     */
    public static function fromArray(array $subtrees): self
    {
        return new self($subtrees);
    }

    /**
     * @internal
     */
    public function add(Subtree $subtree): void
    {
        $this->subtrees[] = $subtree;
    }

    /**
     * @return \ArrayIterator<int,Subtree>|Subtree[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->subtrees);
    }

    public function count(): int
    {
        return count($this->subtrees);
    }

    public function first(): ?Subtree
    {
        if (count($this->subtrees) > 0) {
            $array = $this->subtrees;
            return reset($array);
        }

        return null;
    }
}
