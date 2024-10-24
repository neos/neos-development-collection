<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Processors;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepositoryRegistry\Service\ProjectionService;
use Neos\Neos\Domain\Service\SitePruningService;

/**
 * Content Repository service to perform Projection replays
 *
 * @internal this is currently only used by the {@see SitePruningService}
 */
final class ProjectionReplayProcessor implements ProcessorInterface, ContentRepositoryServiceInterface
{

    public function __construct(
        private readonly ProjectionService $projectionService,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $this->projectionService->replayAllProjections(CatchUpOptions::create());
    }
}
