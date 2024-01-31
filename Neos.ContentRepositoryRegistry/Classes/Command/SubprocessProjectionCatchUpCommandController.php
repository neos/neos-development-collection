<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\SubprocessProjectionCatchUpTrigger;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Cli\CommandController;

/**
 * See {@see SubprocessProjectionCatchUpTrigger} for the side calling this class
 * @internal
 */
class SubprocessProjectionCatchUpCommandController extends CommandController
{
    public function __construct(private readonly ContentRepositoryRegistry $contentRepositoryRegistry)
    {
        parent::__construct();
    }

    /**
     * @param string $contentRepositoryIdentifier
     * @param class-string<ProjectionInterface<ProjectionStateInterface>> $projectionClassName fully qualified class name of the projection to catch up
     * @internal
     */
    public function catchupCommand(string $contentRepositoryIdentifier, string $projectionClassName): void
    {
        $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString($contentRepositoryIdentifier));
        $contentRepository->catchUpProjection($projectionClassName, CatchUpOptions::create());
    }
}
