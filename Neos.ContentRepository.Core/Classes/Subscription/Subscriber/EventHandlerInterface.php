<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Subscriber;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @internal
 */
interface EventHandlerInterface
{
    public function startBatch(): void;
    public function handle(EventInterface $event, EventEnvelope $eventEnvelope, Subscription $subscription): void;
    public function endBatch(): void;
}
