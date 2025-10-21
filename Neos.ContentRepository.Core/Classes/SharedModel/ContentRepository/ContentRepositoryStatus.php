<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\ContentRepository;

use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatusCollection;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStore\Status as EventStoreStatus;

/**
 * The status information of a content repository. Examined via {@see ContentRepositoryMaintainer::status()}
 *
 * @api
 */
final readonly class ContentRepositoryStatus
{
    /**
     * @param EventStoreStatus $eventStoreStatus
     * @param SequenceNumber|null $eventStorePosition The position of the event store. NULL if an error occurred in which case a setup must likely be done, see $eventStoreStatus
     * @param SubscriptionStatusCollection $subscriptionStatus
     */
    private function __construct(
        public EventStoreStatus $eventStoreStatus,
        public SequenceNumber|null $eventStorePosition,
        public SubscriptionStatusCollection $subscriptionStatus,
    ) {
    }

    /**
     * @internal
     */
    public static function create(
        EventStoreStatus $eventStoreStatus,
        SequenceNumber|null $eventStorePosition,
        SubscriptionStatusCollection $subscriptionStatus,
    ): self {
        return new self(
            $eventStoreStatus,
            $eventStorePosition,
            $subscriptionStatus
        );
    }
}
