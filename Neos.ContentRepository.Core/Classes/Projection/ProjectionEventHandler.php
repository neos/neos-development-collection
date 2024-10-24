<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Subscription\Subscriber\EventHandlerInterface;
use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @api
 */
final readonly class ProjectionEventHandler implements EventHandlerInterface
{
    public function __construct(
        private ProjectionInterface $projection,
        private CatchUpHookInterface|null $catchUpHook,
    ) {
    }

    public function startBatch(): void
    {
        $this->catchUpHook?->onBeforeCatchUp();
    }

    public function handle(EventInterface $event, EventEnvelope $eventEnvelope, Subscription $subscription): void
    {
        $this->catchUpHook?->onBeforeEvent($event, $eventEnvelope);
        $this->projection->apply($event, $eventEnvelope);
        $this->catchUpHook?->onAfterEvent($event, $eventEnvelope);
    }

    public function endBatch(): void
    {
        $this->catchUpHook?->onBeforeBatchCompleted();
        $this->catchUpHook?->onAfterCatchUp();
    }
}
