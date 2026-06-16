<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
 * @internal
 * @implements \IteratorAggregate<int,NodeRelationAnchorPoint>
 */
final readonly class NodeRelationAnchorPoints implements \IteratorAggregate, \Countable
{
    /** @param list<NodeRelationAnchorPoint> $items */
    private function __construct(
        private array $items
    ) {
    }

    public static function create(NodeRelationAnchorPoint ...$items): self
    {
        return new self(array_values($items));
    }

    /**
     * @param array<string|int,int> $items
     */
    public static function fromArray(array $items): self
    {
        return new self(
            array_values(array_map(
                NodeRelationAnchorPoint::fromInteger(...),
                $items
            ))
        );
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return list<integer>
     */
    public function toIntArray(): array
    {
        return array_column($this->items, 'value');
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
