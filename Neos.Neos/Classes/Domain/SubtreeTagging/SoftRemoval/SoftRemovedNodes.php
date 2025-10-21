<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\SubtreeTagging\SoftRemoval;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;

/**
 * @implements \IteratorAggregate<SoftRemovedNode>
 * @internal
 */
final readonly class SoftRemovedNodes implements \IteratorAggregate, \Countable
{
    private function __construct(
        /** @var array<string,SoftRemovedNode> indexed by NodeAggregateId */
        private array $items
    ) {
    }

    public static function create(SoftRemovedNode ...$items): self
    {
        $indexedItems = [];
        foreach ($items as $item) {
            $indexedItems[$item->nodeAggregateId->value] = $item;
        }
        return new self($indexedItems);
    }

    /** @param array<SoftRemovedNode> $items */
    public static function fromArray(array $items): self
    {
        return self::create(...$items);
    }

    public function get(NodeAggregateId $key): ?SoftRemovedNode
    {
        return $this->items[$key->value] ?? null;
    }

    public function with(SoftRemovedNode $item): self
    {
        $items = $this->items;
        $items[$item->nodeAggregateId->value] = $item;
        return new self($items);
    }

    public function merge(self $other): self
    {
        return new self(array_merge($this->items, $other->items));
    }

    public function toNodeAggregateIds(): NodeAggregateIds
    {
        return NodeAggregateIds::fromArray(
            array_map(fn (SoftRemovedNode $node) => $node->nodeAggregateId, $this->items)
        );
    }

    public function getIterator(): \Traversable
    {
        yield from array_values($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }
}
