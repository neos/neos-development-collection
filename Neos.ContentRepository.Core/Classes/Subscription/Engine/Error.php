<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Engine;

use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * @internal implementation detail of the catchup
 */
final readonly class Error
{
    private function __construct(
        public SubscriptionId $subscriptionId,
        public string $message,
        public \Throwable|null $throwable,
        public SequenceNumber|null $position,
    ) {
    }

    public static function create(
        SubscriptionId $subscriptionId,
        string $message,
        \Throwable|null $throwable,
        SequenceNumber|null $position,
    ): self {
        return new self(
            $subscriptionId,
            $message,
            $throwable,
            $position
        );
    }
}
