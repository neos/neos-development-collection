<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

/**
 * @implements \IteratorAggregate<SubscriptionStatus>
 * @internal implementation detail of the catchup
 */
final class SubscriptionStatusFilter implements \IteratorAggregate
{
    /**
     * @param array<string, SubscriptionStatus> $statusByValue
     */
    private function __construct(
        private readonly array $statusByValue,
    ) {
    }

    /**
     * @param array<string|SubscriptionStatus> $status
     */
    public static function fromArray(array $status): self
    {
        $statusByValue = [];
        foreach ($status as $singleStatus) {
            if (is_string($singleStatus)) {
                $singleStatus = SubscriptionStatus::from($singleStatus);
            }
            if (!$singleStatus instanceof SubscriptionStatus) {
                throw new \InvalidArgumentException(sprintf('Expected instance of %s, got: %s', SubscriptionStatus::class, get_debug_type($singleStatus)), 1731580994);
            }
            if (array_key_exists($singleStatus->value, $statusByValue)) {
                throw new \InvalidArgumentException(sprintf('Status "%s" is already part of this set', $singleStatus->value), 1731581002);
            }
            $statusByValue[$singleStatus->value] = $singleStatus;
        }
        return new self($statusByValue);
    }

    public static function any(): self
    {
        return new self([]);
    }

    public function getIterator(): \Traversable
    {
        yield from array_values($this->statusByValue);
    }

    public function isEmpty(): bool
    {
        return $this->statusByValue === [];
    }

    /**
     * @return list<string>
     */
    public function toStringArray(): array
    {
        return array_values(array_map(static fn (SubscriptionStatus $id) => $id->value, $this->statusByValue));
    }
}
