<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Store;

use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionIds;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatusFilter;

/**
 * @internal implementation detail of the catchup
 */
final readonly class SubscriptionCriteria
{
    private function __construct(
        public SubscriptionIds|null $ids,
        public SubscriptionStatusFilter $status,
    ) {
    }

    /**
     * @param SubscriptionIds|array<string|SubscriptionId>|null $ids
     * @param SubscriptionStatusFilter|null $status
     */
    public static function create(
        SubscriptionIds|array|null $ids = null,
        ?SubscriptionStatusFilter $status = null,
    ): self {
        if (is_array($ids)) {
            $ids = SubscriptionIds::fromArray($ids);
        }
        return new self(
            $ids,
            $status ?? SubscriptionStatusFilter::any(),
        );
    }

    public static function forEngineCriteriaAndStatus(
        SubscriptionEngineCriteria $criteria,
        SubscriptionStatusFilter|SubscriptionStatus $status,
    ): self {
        if ($status instanceof SubscriptionStatus) {
            $status = SubscriptionStatusFilter::fromArray([$status]);
        }
        return new self(
            $criteria->ids,
            $status,
        );
    }

    public static function noConstraints(): self
    {
        return new self(
            ids: null,
            status: SubscriptionStatusFilter::any(),
        );
    }
}
