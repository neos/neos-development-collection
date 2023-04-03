<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepositoryRegistry\Command\CrCommandController;
use Neos\Flow\Annotations as Flow;

/**
 * Factory for the {@see ProjectionReplayService}
 *
 * @implements ContentRepositoryServiceFactoryInterface<ProjectionReplayService>
 * @internal this is currently only used by the {@see CrCommandController}
 */
#[Flow\Scope("singleton")]
final class ProjectionReplayServiceFactory implements ContentRepositoryServiceFactoryInterface
{

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
    {
        return new ProjectionReplayService($serviceFactoryDependencies->projections, $serviceFactoryDependencies->contentRepository);
    }
}
