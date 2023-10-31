<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<ContentStreamPruner>
 * @api
 */
class ContentStreamPrunerFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentStreamPruner
    {
        return new ContentStreamPruner(
            $serviceFactoryDependencies->contentRepository,
            $serviceFactoryDependencies->eventStore
        );
    }
}
