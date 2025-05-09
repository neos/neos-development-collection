<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<ContentRepositoryMaintainer>
 * @api
 */
class ContentRepositoryMaintainerFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): ContentRepositoryMaintainer {
        return new ContentRepositoryMaintainer(
            $serviceFactoryDependencies->eventStore,
            $serviceFactoryDependencies->subscriptionEngine
        );
    }
}
