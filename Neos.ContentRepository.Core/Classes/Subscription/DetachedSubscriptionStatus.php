<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * Note that the SubscriptionStatus might not be actually Detached yet, as for the marking of detached,
 * setup or catchup has to be run.
 *
 * @api part of the subscription status
 */
final readonly class DetachedSubscriptionStatus
{
    private function __construct(
        public SubscriptionId $subscriptionId,
        public SubscriptionStatus $subscriptionStatus,
        public SequenceNumber $subscriptionPosition
    ) {
    }

    /**
     * @internal implementation detail of the catchup
     */
    public static function create(
        SubscriptionId $subscriptionId,
        SubscriptionStatus $subscriptionStatus,
        SequenceNumber $subscriptionPosition
    ): self {
        return new self($subscriptionId, $subscriptionStatus, $subscriptionPosition);
    }
}
