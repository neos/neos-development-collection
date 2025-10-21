<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * @internal implementation detail of the catchup
 */
final readonly class Subscription
{
    public function __construct(
        public SubscriptionId $id,
        public SubscriptionStatus $status,
        public SequenceNumber $position,
        public SubscriptionError|null $error,
        public \DateTimeImmutable|null $lastSavedAt,
    ) {
    }
}
