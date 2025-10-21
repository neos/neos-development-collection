<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Factory\SubscriberFactoryDependencies;

/**
 * @api for creating a custom content repository graph projection implementation, **not for users of the CR**
 */
interface ContentGraphProjectionFactoryInterface
{
    public function build(
        SubscriberFactoryDependencies $projectionFactoryDependencies,
    ): ContentGraphProjectionInterface;
}
