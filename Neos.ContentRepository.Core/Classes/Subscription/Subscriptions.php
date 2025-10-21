<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * @implements \IteratorAggregate<Subscription>
 * @internal implementation detail of the catchup
 */
final class Subscriptions implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @param array<string, Subscription> $subscriptionsById
     */
    private function __construct(
        private readonly array $subscriptionsById
    ) {
    }

    /**
     * @param array<Subscription> $subscriptions
     */
    public static function fromArray(array $subscriptions): self
    {
        $subscriptionsById = [];
        foreach ($subscriptions as $subscription) {
            if (!$subscription instanceof Subscription) {
                throw new \InvalidArgumentException(sprintf('Expected instance of %s, got: %s', Subscription::class, get_debug_type($subscription)), 1729679774);
            }
            if (array_key_exists($subscription->id->value, $subscriptionsById)) {
                throw new \InvalidArgumentException(sprintf('Subscription with id "%s" is contained multiple times in this set', $subscription->id->value), 1731580354);
            }
            $subscriptionsById[$subscription->id->value] = $subscription;
        }
        return new self($subscriptionsById);
    }

    public static function createEmpty(): self
    {
        return self::fromArray([]);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->subscriptionsById;
    }

    public function isEmpty(): bool
    {
        return $this->subscriptionsById === [];
    }

    public function first(): ?Subscription
    {
        foreach ($this->subscriptionsById as $subscription) {
            return $subscription;
        }
        return null;
    }

    public function count(): int
    {
        return count($this->subscriptionsById);
    }

    public function contain(SubscriptionId $subscriptionId): bool
    {
        return array_key_exists($subscriptionId->value, $this->subscriptionsById);
    }

    public function get(SubscriptionId $subscriptionId): Subscription
    {
        if (!$this->contain($subscriptionId)) {
            throw new \InvalidArgumentException(sprintf('Subscription with id "%s" not part of this set', $subscriptionId->value), 1723567808);
        }
        return $this->subscriptionsById[$subscriptionId->value];
    }

    public function without(SubscriptionId $subscriptionId): self
    {
        $subscriptionsById = $this->subscriptionsById;
        unset($subscriptionsById[$subscriptionId->value]);
        return new self($subscriptionsById);
    }

    /**
     * @param \Closure(Subscription): bool $callback
     */
    public function filter(\Closure $callback): self
    {
        return self::fromArray(array_filter($this->subscriptionsById, $callback));
    }

    /**
     * @template T
     * @param \Closure(Subscription): T $callback
     * @return array<T>
     */
    public function map(\Closure $callback): array
    {
        return array_map($callback, $this->subscriptionsById);
    }

    public function with(Subscription $subscription): self
    {
        return new self([...$this->subscriptionsById, $subscription->id->value => $subscription]);
    }

    public function getIds(): SubscriptionIds
    {
        return SubscriptionIds::fromArray(array_map(
            fn (Subscription $subscription) => $subscription->id,
            iterator_to_array($this->subscriptionsById)
        ));
    }

    /**
     * @return iterable<Subscription>
     */
    public function jsonSerialize(): iterable
    {
        return array_values($this->subscriptionsById);
    }

    public function lowestPosition(): SequenceNumber|null
    {
        if ($this->subscriptionsById === []) {
            return null;
        }
        return SequenceNumber::fromInteger(
            min(
                array_map(
                    fn (Subscription $subscription) => $subscription->position->value,
                    $this->subscriptionsById
                )
            )
        );
    }
}
