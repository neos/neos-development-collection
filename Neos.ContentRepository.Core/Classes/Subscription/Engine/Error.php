<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Engine;

use Neos\ContentRepository\Core\Subscription\SubscriptionId;

/**
 * @internal
 */
final class Error
{
    public function __construct(
        public readonly SubscriptionId $subscriptionId,
        public readonly string $message,
        public readonly \Throwable $throwable,
    ) {
    }
}
