<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Service;

use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<ContentStreamPruner>
 */
class ContentStreamPrunerFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentStreamPruner
    {
        return new ContentStreamPruner($serviceFactoryDependencies->contentRepository, $serviceFactoryDependencies->eventStore);
    }
}
