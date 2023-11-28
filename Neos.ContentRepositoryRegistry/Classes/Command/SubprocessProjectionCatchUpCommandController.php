<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\SubprocessProjectionCatchUpTrigger;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

/**
 * See {@see SubprocessProjectionCatchUpTrigger} for the side calling this class
 * @internal
 */
class SubprocessProjectionCatchUpCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @param string $contentRepositoryIdentifier
     * @param class-string<ProjectionInterface<ProjectionStateInterface>> $projectionClassName fully qualified class name of the projection to catch up
     * @internal
     */
    public function catchupCommand(string $contentRepositoryIdentifier, string $projectionClassName): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepository->catchUpProjection($projectionClassName, CatchUpOptions::create());
    }
}
