<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/**
 * @implements \IteratorAggregate<int,UserId>
 */
final readonly class UserIds implements \IteratorAggregate
{
    /** @param array<string,UserId> $items */
    private function __construct(
        private array $items
    ) {
    }

    public static function create(
        UserId ...$items
    ): self {
        $indexed = [];
        foreach ($items as $item) {
            $indexed[$item->value] = $item;
        }
        return new self(
            $indexed
        );
    }

    public function contain(UserId $userId): bool
    {
        return array_key_exists($userId->value, $this->items);
    }

    /** @return list<string> */
    public function toStringArray(): array
    {
        return array_keys($this->items);
    }

    public function getIterator(): \Traversable
    {
        yield from array_values($this->items);
    }
}
