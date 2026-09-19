<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\ViewModel\Restore;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<int,RestorableNode>
 * @internal for communication within the Workspace UI only
 */
#[Flow\Proxy(false)]
final readonly class RestorableNodes implements \IteratorAggregate, \Countable
{
    /**
     * @param array<int,RestorableNode> $items
     */
    private function __construct(
        private array $items,
    ) {
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    public static function list(RestorableNode ...$items): self
    {
        return new self(array_values($items));
    }

    /**
     * @return array<int,string>
     */
    public function getLabels(): array
    {
        return array_map(
            fn (RestorableNode $item): string => $item->label,
            $this->items,
        );
    }

    public function getNodeAggregateIds(): NodeAggregateIds
    {
        $values = [];
        foreach ($this->items as $item) {
            $values[$item->nodeAggregateId->value] = $item->nodeAggregateId;
        }

        return NodeAggregateIds::fromArray(array_values($values));
    }

    public function union(self $other): self
    {
        return new self(array_merge(
            $this->items,
            $other->items
        ));
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
