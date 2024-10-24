<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Store;

use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionGroups;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionIds;

/**
 * @internal
 */
final class SubscriptionCriteria
{
    /**
     * @param list<SubscriptionStatus>|null $status
     */
    private function __construct(
        public readonly SubscriptionIds|null $ids,
        public readonly SubscriptionGroups|null $groups,
        public readonly array|null $status,
    ) {
    }

    /**
     * @param SubscriptionIds|array<string|SubscriptionId>|null $ids
     * @param SubscriptionGroups|list<string>|null $groups
     * @param list<SubscriptionStatus>|null $status
     */
    public static function create(
        SubscriptionIds|array $ids = null,
        SubscriptionGroups|array $groups = null,
        array $status = null,
    ): self {
        if (is_array($ids)) {
            $ids = SubscriptionIds::fromArray($ids);
        }
        if (is_array($groups)) {
            $groups = SubscriptionGroups::fromArray($groups);
        }
        return new self(
            $ids,
            $groups,
            $status,
        );
    }

    public static function noConstraints(): self
    {
        return new self(
            ids: null,
            groups: null,
            status: null,
        );
    }

    public static function withStatus(SubscriptionStatus $status): self
    {
        return new self(
            ids: null,
            groups: null,
            status: [$status],
        );
    }
}
