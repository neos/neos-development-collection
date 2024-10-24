<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Processors;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepositoryRegistry\Command\CrCommandController;
use Neos\ContentRepositoryRegistry\Processors\ProjectionReplayProcessor;
use Neos\ContentRepositoryRegistry\Service\ProjectionService;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\SitePruningService;

/**
 * Factory for the {@see ProjectionReplayProcessor}
 *
 * @implements ContentRepositoryServiceFactoryInterface<ProjectionReplayProcessor>
 * @internal this is currently only used by the {@see SitePruningService}
 */
#[Flow\Scope("singleton")]
final class ProjectionReplayProcessorFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
    {
        return new ProjectionReplayProcessor(
            new ProjectionService(
                $serviceFactoryDependencies->projections,
                $serviceFactoryDependencies->contentRepository,
                $serviceFactoryDependencies->eventStore,
            )
        );
    }
}
