<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Subscriber;

use Neos\ContentRepository\Core\Subscription\RunMode;
use Neos\ContentRepository\Core\Subscription\SubscriptionGroup;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;

/**
 * @internal
 */
final class Subscriber
{
    public function __construct(
        public readonly SubscriptionId $id,
        public readonly SubscriptionGroup $group,
        public readonly RunMode $runMode,
        public readonly EventHandlerInterface $handler,
    ) {
    }
}
