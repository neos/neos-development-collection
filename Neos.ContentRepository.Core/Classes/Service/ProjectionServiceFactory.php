<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<ProjectionService>
 * @api
 */
class ProjectionServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ProjectionService
    {
        return new ProjectionService(
            $serviceFactoryDependencies->projections,
            $serviceFactoryDependencies->contentRepository,
            $serviceFactoryDependencies->eventStore,
        );
    }
}
