<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\RetryStrategy;

use Psr\Clock\ClockInterface;
use Neos\ContentRepository\Core\Subscription\Subscription;

/**
 * @internal
 */
final class ClockBasedRetryStrategy implements RetryStrategy
{
    public const DEFAULT_BASE_DELAY = 5;
    public const DEFAULT_DELAY_FACTOR = 2;
    public const DEFAULT_MAX_ATTEMPTS = 5;

    /**
     * @param int $baseDelay in seconds
     * @param positive-int $maxAttempts
     */
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly int $baseDelay = self::DEFAULT_BASE_DELAY,
        private readonly float $delayFactor = self::DEFAULT_DELAY_FACTOR,
        private readonly int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
    ) {
    }

    public function shouldRetry(Subscription $subscription): bool
    {
        if ($subscription->retryAttempt >= $this->maxAttempts) {
            return false;
        }

        $lastSavedAt = $subscription->lastSavedAt;

        if ($lastSavedAt === null) {
            return false;
        }

        $nextRetryDate = $this->calculateNextRetryDate($lastSavedAt, $subscription->retryAttempt);

        return $nextRetryDate <= $this->clock->now();
    }

    private function calculateNextRetryDate(\DateTimeImmutable $lastDate, int $attempt): \DateTimeImmutable
    {
        return $lastDate->modify(sprintf('+%d seconds', $this->calculateDelay($attempt)));
    }

    private function calculateDelay(int $attempt): int
    {
        return (int)round($this->baseDelay * ($this->delayFactor ** $attempt));
    }
}
