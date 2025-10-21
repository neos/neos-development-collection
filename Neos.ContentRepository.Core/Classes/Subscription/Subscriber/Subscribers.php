<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Subscriber;

use Neos\ContentRepository\Core\Subscription\SubscriptionId;

/**
 * A collection of the registered subscribers.
 *
 * Currently only projections are the available subscribers, but when the concept is extended,
 * other *Subscriber value objects will also be hold in this set.
 * Like a possible "ListeningSubscriber" to only listen to events without the capabilities of a full-blown projection.
 *
 * @implements \IteratorAggregate<ProjectionSubscriber>
 * @internal implementation detail of the catchup
 */
final class Subscribers implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @param array<string, ProjectionSubscriber> $subscribersById
     */
    private function __construct(
        private readonly array $subscribersById
    ) {
    }

    /**
     * @param array<ProjectionSubscriber> $subscribers
     */
    public static function fromArray(array $subscribers): self
    {
        $subscribersById = [];
        foreach ($subscribers as $subscriber) {
            if (!$subscriber instanceof ProjectionSubscriber) {
                throw new \InvalidArgumentException(sprintf('Expected instance of %s, got: %s', ProjectionSubscriber::class, get_debug_type($subscriber)), 1721731490);
            }
            if (array_key_exists($subscriber->id->value, $subscribersById)) {
                throw new \InvalidArgumentException(sprintf('Subscriber with id "%s" is already part of this set', $subscriber->id->value), 1721731494);
            }
            $subscribersById[$subscriber->id->value] = $subscriber;
        }
        return new self($subscribersById);
    }

    public static function createEmpty(): self
    {
        return self::fromArray([]);
    }

    public function with(ProjectionSubscriber $subscriber): self
    {
        return new self([...$this->subscribersById, $subscriber->id->value => $subscriber]);
    }

    public function get(SubscriptionId $id): ProjectionSubscriber
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
        yield from array_values($this->subscribersById);
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
