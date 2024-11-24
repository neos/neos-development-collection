<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Subscriber;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionStoreInterface;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @internal
 */
final class ProjectionSubscriber
{
    /**
     * @param ProjectionInterface<ProjectionStateInterface> $projection
     */
    public function __construct(
        public readonly SubscriptionId $id,
        public readonly SubscriptionStoreInterface $store,
        public readonly ProjectionInterface $projection,
        private readonly ?CatchUpHookInterface $catchUpHook,
    ) {
    }

    public function onBeforeCatchUp(SubscriptionStatus $subscriptionStatus): void
    {
        $this->catchUpHook?->onBeforeCatchUp($subscriptionStatus);
    }

    public function handle(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        $this->catchUpHook?->onBeforeEvent($event, $eventEnvelope);
        $this->projection->apply($event, $eventEnvelope);
        $this->catchUpHook?->onAfterEvent($event, $eventEnvelope);
    }

    public function onAfterCatchUp(): void
    {
        $this->catchUpHook?->onAfterCatchUp();
    }
}
