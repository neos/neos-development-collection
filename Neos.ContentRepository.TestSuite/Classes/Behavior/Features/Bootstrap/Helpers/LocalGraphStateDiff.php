<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\Flow\Annotations as Flow;

/**
 * The difference between two local graph states, indexed by dimension space point hash
 *
 * @implements \IteratorAggregate<string,LocalSubgraphStateDiff>
 */
#[Flow\Proxy(false)]
final readonly class LocalGraphStateDiff implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @param array<string,LocalSubgraphStateDiff> $items
     */
    private function __construct(
        public array $items
    ) {
    }

    /**
     * @param array<string,LocalSubgraphStateDiff> $items
     */
    public static function create(array $items): self
    {
        return new self($items);
    }

    public static function fromLocalGraphState(LocalGraphState $localGraphState): self
    {
        return new self(array_filter(
            array_map(
                fn (?LocalSubgraphState $item): ?LocalSubgraphStateDiff => $item ? LocalSubgraphStateDiff::fromLocalSubgraphState($item) : null,
                $localGraphState->items,
            )
        ));
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return array<string,LocalSubgraphStateDiff>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
