<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\SubprocessProjectionCatchUpTrigger;
use Neos\ContentRepository\Core\Factory\ContentRepositoryIdentifier;
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
    protected $contentRepositoryRegistry;

    public function catchupCommand(string $contentRepositoryIdentifier, string $projectionClassName): void
    {

        $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryIdentifier::fromString($contentRepositoryIdentifier));
        $contentRepository->catchUpProjection($projectionClassName);
    }
}
