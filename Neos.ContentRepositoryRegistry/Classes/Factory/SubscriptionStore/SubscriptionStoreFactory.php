<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\SubscriptionStore;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionStoreInterface;
use Psr\Clock\ClockInterface;

/**
 * @api
 */
final readonly class SubscriptionStoreFactory implements SubscriptionStoreFactoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, ClockInterface $clock, array $options): SubscriptionStoreInterface
    {
        return new DoctrineSubscriptionStore(sprintf('cr_%s_subscriptions', $contentRepositoryId->value), $this->connection, $clock);
    }
}
