<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;

use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatusCollection;
use Neos\Error\Messages\Warning;

/**
 * @api as returned by {@see ContentRepositoryMaintainer}
 */
final class SetupWarning extends Warning
{
    private function __construct(
        string $message,
        int $code,
        public readonly SubscriptionStatusCollection $skippedSubscriptions
    ) {
        parent::__construct($message, $code);
    }

    public static function becauseSubscriptionsWereSkipped(SubscriptionStatusCollection $subscriptionStatusCollection): self
    {
        return new self(
            message: sprintf('%d subscriptions were skipped during setup', $subscriptionStatusCollection->count()),
            code: 1782298982,
            skippedSubscriptions: $subscriptionStatusCollection,
        );
    }
}
