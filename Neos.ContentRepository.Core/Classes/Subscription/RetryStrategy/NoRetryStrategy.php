<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\RetryStrategy;

use Neos\ContentRepository\Core\Subscription\Subscription;

/**
 * @internal
 */
final class NoRetryStrategy implements RetryStrategy
{
    public function shouldRetry(Subscription $subscription): bool
    {
        return false;
    }
}
