<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepositoryRegistry\Command\CrCommandController;
use Neos\Flow\Annotations as Flow;

/**
 * Factory for the {@see ProjectionService}
 *
 * @implements ContentRepositoryServiceFactoryInterface<ProjectionService>
 * @internal this is currently only used by the {@see CrCommandController}
 */
#[Flow\Scope("singleton")]
final class ProjectionServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
    {
        return new ProjectionService(
            $serviceFactoryDependencies->projections,
            $serviceFactoryDependencies->contentRepository,
            $serviceFactoryDependencies->eventStore,
        );
    }
}
