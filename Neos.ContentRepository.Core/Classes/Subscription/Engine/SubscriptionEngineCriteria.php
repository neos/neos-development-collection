<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Engine;

use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionIds;

/**
 * @internal implementation detail of the catchup
 */
final class SubscriptionEngineCriteria
{
    private function __construct(
        public readonly SubscriptionIds|null $ids
    ) {
    }

    /**
     * @param SubscriptionIds|array<string|SubscriptionId>|null $ids
     */
    public static function create(
        SubscriptionIds|array|null $ids = null
    ): self {
        if (is_array($ids)) {
            $ids = SubscriptionIds::fromArray($ids);
        }
        return new self(
            $ids
        );
    }

    public static function noConstraints(): self
    {
        return new self(
            ids: null
        );
    }
}
