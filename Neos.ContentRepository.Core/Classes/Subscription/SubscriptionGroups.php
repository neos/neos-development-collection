<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

/**
 * @implements \IteratorAggregate<SubscriptionGroup>
 * @internal
 */
final class SubscriptionGroups implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @param array<SubscriptionGroup> $items
     */
    private function __construct(
        private readonly array $items
    ) {
    }

    /**
     * @param list<SubscriptionGroup|string> $items
     */
    public static function fromArray(array $items): self
    {
        return new self(array_map(static fn ($item) => $item instanceof SubscriptionGroup ? $item : SubscriptionGroup::fromString($item), $items));
    }

    public static function none(): self
    {
        return self::fromArray([]);
    }

    public function getIterator(): \Traversable
    {
        return yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function contain(SubscriptionGroup $group): bool
    {
        foreach ($this->items as $item) {
            if ($item->equals($group)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<string>
     */
    public function toStringArray(): array
    {
        return array_map(static fn (SubscriptionGroup $group) => $group->value, $this->items);
    }

    public function jsonSerialize(): mixed
    {
        return array_values($this->items);
    }
}
