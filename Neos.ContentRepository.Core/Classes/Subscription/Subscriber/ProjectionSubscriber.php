<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Subscriber;

use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;

/**
 * @internal implementation detail of the catchup
 */
final readonly class ProjectionSubscriber
{
    /**
     * @param ProjectionInterface<ProjectionStateInterface> $projection
     */
    public function __construct(
        public SubscriptionId $id,
        public ProjectionInterface $projection,
        public ?CatchUpHookInterface $catchUpHook
    ) {
    }
}
