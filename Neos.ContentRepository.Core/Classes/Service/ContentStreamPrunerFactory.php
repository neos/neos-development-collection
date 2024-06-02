<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\ContentGraphAdapter;
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
        $contentGraphAdapter = $serviceFactoryDependencies->contentRepository->projectionState(ContentGraphAdapter::class);
        return new ContentStreamPruner(
            $serviceFactoryDependencies->contentRepository,
            $serviceFactoryDependencies->eventStore,
            $contentGraphAdapter,
        );
    }
}
