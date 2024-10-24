<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Subscriber;

use Neos\ContentRepository\Core\Subscription\SubscriptionId;

/**
 * @implements \IteratorAggregate<Subscriber>
 * @internal
 */
final class Subscribers implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @param array<string, Subscriber> $subscribersById
     */
    private function __construct(
        private readonly array $subscribersById
    ) {
    }

    /**
     * @param array<Subscriber> $subscribers
     */
    public static function fromArray(array $subscribers): self
    {
        $subscribersById = [];
        foreach ($subscribers as $subscriber) {
            if (!$subscriber instanceof Subscriber) {
                throw new \InvalidArgumentException(sprintf('Expected instance of %s, got: %s', Subscriber::class, get_debug_type($subscriber)), 1721731490);
            }
            if (array_key_exists($subscriber->id->value, $subscribersById)) {
                throw new \InvalidArgumentException(sprintf('Subscriber with id "%s" already part of this set', $subscriber->id->value), 1721731494);
            }
            $subscribersById[$subscriber->id->value] = $subscriber;
        }
        return new self($subscribersById);
    }

    public static function none(): self
    {
        return self::fromArray([]);
    }

    public function get(SubscriptionId $id): Subscriber
    {
        if (!$this->contain($id)) {
            throw new \InvalidArgumentException(sprintf('Subscriber with the subscription id "%s" not found.', $id->value), 1721731490);
        }
        return $this->subscribersById[$id->value];
    }

    public function contain(SubscriptionId $id): bool
    {
        return array_key_exists($id->value, $this->subscribersById);
    }

    public function getIterator(): \Traversable
    {
        return yield from $this->subscribersById;
    }

    public function count(): int
    {
        return count($this->subscribersById);
    }

    /**
     * @return iterable<mixed>
     */
    public function jsonSerialize(): iterable
    {
        return array_values($this->subscribersById);
    }
}
