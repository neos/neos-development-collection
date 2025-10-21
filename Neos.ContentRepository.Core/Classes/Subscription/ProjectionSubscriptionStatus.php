<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * @api part of the subscription status
 */
final readonly class ProjectionSubscriptionStatus
{
    private function __construct(
        public SubscriptionId $subscriptionId,
        public SubscriptionStatus $subscriptionStatus,
        public SequenceNumber $subscriptionPosition,
        public SubscriptionError|null $subscriptionError,
        public ProjectionStatus $setupStatus,
    ) {
    }

    /**
     * @internal implementation detail of the catchup
     */
    public static function create(
        SubscriptionId $subscriptionId,
        SubscriptionStatus $subscriptionStatus,
        SequenceNumber $subscriptionPosition,
        SubscriptionError|null $subscriptionError,
        ProjectionStatus $setupStatus
    ): self {
        return new self($subscriptionId, $subscriptionStatus, $subscriptionPosition, $subscriptionError, $setupStatus);
    }
}
