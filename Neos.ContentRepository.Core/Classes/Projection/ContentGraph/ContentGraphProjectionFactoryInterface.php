<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Factory\SubscriberFactoryDependencies;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;

/**
 * @api for creating a custom content repository graph projection implementation, **not for users of the CR**
 */
interface ContentGraphProjectionFactoryInterface
{
    public function getSubscriptionId(): SubscriptionId;

    public function build(
        SubscriberFactoryDependencies $projectionFactoryDependencies,
    ): ContentGraphProjectionInterface;
}
