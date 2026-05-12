<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

/**
 * The difference between two graph states, indexed by node aggregate id
 *
 * @implements \IteratorAggregate<string,LocalGraphStateDiff>
 */
final readonly class GraphStateDiff implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @param array<string,LocalGraphStateDiff> $items
     */
    private function __construct(
        public array $items
    ) {
    }

    /**
     * @param array<string,LocalGraphStateDiff> $items
     */
    public static function create(array $items): self
    {
        return new self($items);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return array<string,LocalGraphStateDiff>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
