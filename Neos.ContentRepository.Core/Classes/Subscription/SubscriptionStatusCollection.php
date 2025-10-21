<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

/**
 * A collection of the status of the subscribers.
 *
 * Currently only projections are the available subscribers, but when the concept is extended,
 * other *SubscriptionStatus value objects will also be hold in this set.
 * Like "ListeningSubscriptionStatus" if a "ListeningSubscriber" is introduced.
 *
 * In case the subscriber is not available currently - e.g. will be detached, a {@see DetachedSubscriptionStatus} will be returned.
 * Note that ProjectionSubscriptionStatus with status == Detached can be returned, if the projection is installed again!
 *
 * @api
 * @implements \IteratorAggregate<ProjectionSubscriptionStatus|DetachedSubscriptionStatus>
 */
final readonly class SubscriptionStatusCollection implements \IteratorAggregate
{
    /**
     * @var array<ProjectionSubscriptionStatus|DetachedSubscriptionStatus> $items
     */
    private array $items;

    private function __construct(
        ProjectionSubscriptionStatus|DetachedSubscriptionStatus ...$items,
    ) {
        $this->items = $items;
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @param array<ProjectionSubscriptionStatus|DetachedSubscriptionStatus> $items
     */
    public static function fromArray(array $items): self
    {
        return new self(...$items);
    }

    public function first(): ProjectionSubscriptionStatus|DetachedSubscriptionStatus|null
    {
        foreach ($this->items as $status) {
            return $status;
        }
        return null;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
