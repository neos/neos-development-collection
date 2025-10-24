<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * A collection of in-memory node records
 *
 * @implements \IteratorAggregate<InMemoryNodeRecord>
 * @internal
 */
final class InMemoryNodeRecords implements \IteratorAggregate
{
    /**
     * @var InMemoryNodeRecord[]
     */
    private array $items;

    public function __construct(InMemoryNodeRecord ...$items)
    {
        $this->items = $items;
    }

    public static function create(InMemoryNodeRecord ...$items): self
    {
        return new self(...$items);
    }

    public function reverse(): self
    {
        return new self(...array_reverse($this->items));
    }

    public function removeIfContained(InMemoryNodeRecord $record): void
    {
        foreach ($this->items as $i => $item) {
            if ($item === $record) {
                unset($this->items[$i]);
                $this->items = array_values($this->items);
                return;
            }
        }
    }

    public function insert(InMemoryNodeRecord $nodeRecord, ?NodeAggregateId $succeedingSiblingId): void
    {
        if (!$succeedingSiblingId) {
            $this->items[] = $nodeRecord;
        } else {
            $nodeAggregateIds = array_map(
                fn (InMemoryNodeRecord $nodeRecord): NodeAggregateId => $nodeRecord->nodeAggregateId,
                array_values($this->items),
            );
            $succeedingSiblingPosition = array_search($succeedingSiblingId, $nodeAggregateIds);
            if ($succeedingSiblingPosition !== false) {
                array_splice($this->items, $succeedingSiblingPosition, 0, [$nodeRecord]);
            }
        }
    }

    /**
     * @return \Traversable<InMemoryNodeRecord>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
